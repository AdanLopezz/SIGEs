<?php

namespace Tests\Feature;

use App\Mail\AvisoEscolar;
use App\Jobs\EnviarNotificacion;
use App\Models\ComNotificacion;
use App\Models\ComNotificacionIntento;
use App\Models\PerPersona;
use App\Support\EnvioDeNotificaciones;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Tests\TestCase;

/** Integración aislada: no ejecuta migraciones ni toca PostgreSQL o correo real. */
class NotificacionesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        config(['siges.notificaciones.habilitadas' => true, 'siges.notificaciones.mailer' => 'smtp']);
        // Esquema mínimo para probar la interacción de envío, tutela y bitácora.
        foreach ([
            'per_persona' => 'id integer primary key, curp text, email_personal text',
            'users' => 'id integer primary key, per_persona_id integer',
            'cat_tipo_incidencia' => 'id integer primary key, notifica_tutor boolean',
            'trx_incidencia' => 'id integer primary key, per_alumno_id integer, cat_tipo_incidencia_id integer',
            'per_tutor_alumno' => 'id integer primary key, per_alumno_id integer, per_persona_id integer, activo boolean, recibe_notificaciones boolean',
            'com_notificacion' => 'id integer primary key autoincrement, per_persona_id integer, trx_incidencia_id integer, canal text, asunto text, mensaje text, estado text, created_at datetime, updated_at datetime',
            'com_notificacion_intento' => 'id integer primary key autoincrement, com_notificacion_id integer, fecha_intento datetime, exitoso boolean, resultado text',
            'sys_auditoria' => 'id integer primary key autoincrement, user_id integer, tabla_afectada text, registro_id integer, accion text, motivo text, ip_address text, created_at datetime',
            'sys_auditoria_detalle' => 'id integer primary key autoincrement, sys_auditoria_id integer, campo text, valor_anterior text, valor_nuevo text',
        ] as $tabla => $columnas) {
            DB::statement("CREATE TABLE {$tabla} ({$columnas})");
        }
        DB::table('per_persona')->insert([
            ['id' => 1, 'curp' => PerPersona::CURP_SISTEMA, 'email_personal' => null],
            ['id' => 2, 'curp' => 'PRUEBA', 'email_personal' => 'tutor@example.test'],
        ]);
        DB::table('users')->insert(['id' => 1, 'per_persona_id' => 1]);
        DB::table('cat_tipo_incidencia')->insert(['id' => 1, 'notifica_tutor' => true]);
        DB::table('trx_incidencia')->insert(['id' => 1, 'per_alumno_id' => 10, 'cat_tipo_incidencia_id' => 1]);
        DB::table('per_tutor_alumno')->insert(['id' => 1, 'per_alumno_id' => 10, 'per_persona_id' => 2, 'activo' => true, 'recibe_notificaciones' => true]);
    }

    private function aviso(array $datos = []): ComNotificacion
    {
        return ComNotificacion::create($datos + ['per_persona_id' => 2, 'trx_incidencia_id' => 1,
            'canal' => 'EMAIL', 'asunto' => 'Información privada', 'mensaje' => 'Contenido confidencial', 'estado' => 'PENDIENTE']);
    }

    private function transporte(?\Throwable $error = null, int $veces = 1): void
    {
        $mailer = Mockery::mock();
        Mail::shouldReceive('mailer')->with('smtp')->times($veces)->andReturn($mailer);
        $mailer->shouldReceive('to')->with('tutor@example.test')->times($veces)->andReturnSelf();
        $envio = $mailer->shouldReceive('send')->with(Mockery::type(AvisoEscolar::class))->times($veces);
        $error ? $envio->andThrow($error) : $envio->andReturn(Mockery::mock(SentMessage::class));
    }

    public function test_envia_una_sola_vez_y_audita_sin_copiar_correo_al_intento(): void
    {
        $aviso = $this->aviso();
        $this->transporte();
        $envios = app(EnvioDeNotificaciones::class);
        $envios->enviar($aviso->id);
        $envios->enviar($aviso->id);
        $this->assertSame('ENVIADA', $aviso->fresh()->estado);
        $this->assertSame(1, $aviso->intentos()->count());
        $this->assertTrue($aviso->intentos()->first()->exitoso);
        $this->assertDatabaseHas('sys_auditoria', ['tabla_afectada' => 'com_notificacion_intento', 'accion' => 'UPDATE']);
        $this->assertStringNotContainsString('tutor@example.test', $aviso->intentos()->first()->resultado);
    }

    public function test_omite_leidas_enviadas_otro_canal_y_sin_origen(): void
    {
        Mail::shouldReceive('mailer')->never();
        foreach ([['estado' => 'LEIDA'], ['estado' => 'ENVIADA'], ['canal' => 'APP'], ['canal' => 'WHATSAPP'], ['trx_incidencia_id' => null]] as $datos) {
            $aviso = $this->aviso($datos);
            app(EnvioDeNotificaciones::class)->enviar($aviso->id);
            $this->assertSame(0, $aviso->intentos()->count());
            $this->assertSame($aviso->estado, $aviso->fresh()->estado);
        }
    }

    public function test_la_lectura_no_se_pierde_con_un_modelo_desactualizado(): void
    {
        $aviso = $this->aviso();
        $viejo = $aviso->fresh();
        $aviso->marcarLeida();
        app(EnvioDeNotificaciones::class)->enviar($viejo->id);
        $viejo->marcarLeida();
        $this->assertSame('LEIDA', $viejo->estado);
        $this->assertSame(0, $aviso->intentos()->count());
    }

    public function test_revalida_tutela_del_alumno_y_preferencia(): void
    {
        Mail::shouldReceive('mailer')->never();
        foreach ([['activo' => false], ['activo' => true, 'recibe_notificaciones' => false], ['recibe_notificaciones' => true, 'per_alumno_id' => 99]] as $cambio) {
            DB::table('per_tutor_alumno')->where('id', 1)->update($cambio);
            $aviso = $this->aviso();
            app(EnvioDeNotificaciones::class)->enviar($aviso->id);
            $this->assertSame('FALLIDA', $aviso->fresh()->estado);
            $this->assertFalse(app(EnvioDeNotificaciones::class)->puedeIntentar($aviso->fresh()));
        }
    }

    public function test_rechaza_correo_invalido_y_dominio_de_cuentas(): void
    {
        Mail::shouldReceive('mailer')->never();
        foreach ([null, 'incorrecto', 'usuario@siges.com', 'USUARIO@SIGES.COM'] as $correo) {
            DB::table('per_persona')->where('id', 2)->update(['email_personal' => $correo]);
            $aviso = $this->aviso();
            app(EnvioDeNotificaciones::class)->enviar($aviso->id);
            $this->assertSame('FALLIDA', $aviso->fresh()->estado);
        }
    }

    public function test_reintentos_smtp_respetan_espera_y_limite(): void
    {
        $aviso = $this->aviso();
        $this->transporte(new UnexpectedResponseException('Rechazo con datos que no deben guardarse', 450), 3);
        $envios = app(EnvioDeNotificaciones::class);
        for ($i = 0; $i < 3; $i++) {
            $envios->enviar($aviso->id);
            $this->assertFalse($envios->puedeIntentar($aviso->fresh()));
            $this->travel(6)->minutes();
        }
        $envios->enviar($aviso->id);
        $this->assertSame(3, $aviso->intentos()->count());
        $this->assertFalse($envios->puedeIntentar($aviso->fresh()));
        $this->assertSame(ComNotificacionIntento::ERROR, $aviso->intentos()->first()->resultado);
    }

    public function test_error_incierto_no_se_reenvia_automaticamente(): void
    {
        $aviso = $this->aviso();
        $this->transporte(new \RuntimeException('credencial-que-no-debe-filtrarse'));
        $envios = app(EnvioDeNotificaciones::class);
        $envios->enviar($aviso->id);
        $this->travel(10)->minutes();
        $envios->enviar($aviso->id);
        $this->assertSame(1, $aviso->intentos()->count());
        $this->assertSame(ComNotificacionIntento::INCIERTO, $aviso->intentos()->first()->resultado);
        $this->expectException(ValidationException::class);
        $envios->reprogramar($aviso, 'Revisado', false);
    }

    public function test_reserva_interrumpida_requiere_revision_y_conserva_historial(): void
    {
        $aviso = $this->aviso();
        $reserva = $aviso->intentos()->create(['fecha_intento' => now()->subMinutes(6), 'exitoso' => false, 'resultado' => ComNotificacionIntento::EN_PROCESO]);
        $envios = app(EnvioDeNotificaciones::class);
        $this->assertFalse($envios->puedeIntentar($aviso));
        $envios->reprogramar($aviso, 'Proveedor confirma ausencia de entrega', true);
        $this->assertSame(ComNotificacionIntento::INCIERTO, $reserva->fresh()->resultado);
        $this->assertTrue($envios->puedeIntentar($aviso->fresh()));
        $this->transporte();
        $envios->enviar($aviso->id);
        $this->assertSame('ENVIADA', $aviso->fresh()->estado);
        $this->assertSame(3, $aviso->intentos()->count());
    }

    public function test_no_reprograma_avisos_leidos(): void
    {
        $aviso = $this->aviso(['estado' => 'LEIDA']);
        $this->expectException(ValidationException::class);
        app(EnvioDeNotificaciones::class)->reprogramar($aviso, 'Motivo', true);
    }

    public function test_desactivado_o_mailer_de_log_no_acredita_envios(): void
    {
        Mail::shouldReceive('mailer')->never();
        $aviso = $this->aviso();
        config(['siges.notificaciones.habilitadas' => false]);
        app(EnvioDeNotificaciones::class)->enviar($aviso->id);
        config(['siges.notificaciones.habilitadas' => true, 'siges.notificaciones.mailer' => 'log']);
        app(EnvioDeNotificaciones::class)->enviar($aviso->id);
        $this->assertSame(0, $aviso->intentos()->count());
    }

    public function test_correo_contiene_enlace_sin_datos_de_la_incidencia(): void
    {
        $correo = new AvisoEscolar(123);
        $html = $correo->render();
        $this->assertStringContainsString(route('avisos.show', 123), $html);
        $this->assertStringNotContainsString('Información privada', $html);
        $this->assertStringNotContainsString('Contenido confidencial', $html);
    }

    public function test_rutas_administrativas_exigen_permiso_y_sesion(): void
    {
        foreach (['notificaciones.index', 'notificaciones.show', 'notificaciones.reintentar'] as $nombre) {
            $ruta = app('router')->getRoutes()->getByName($nombre);
            $this->assertContains('permission:usuarios.administrar', $ruta->middleware());
            $this->assertContains('auth:web', $ruta->middleware());
        }
        $this->get('/notificaciones')->assertRedirect(route('login'));
    }

    public function test_usuario_sin_permiso_no_consulta_ni_reintenta(): void
    {
        // Se aísla el permiso del módulo, conservando autenticación y middleware de Spatie.
        $this->withoutMiddleware(\App\Http\Middleware\RequiereRol::class);
        $usuario = Mockery::mock(\App\Models\User::class)->makePartial();
        $usuario->id = 1;
        $usuario->shouldReceive('canAny')->andReturn(false);
        $this->actingAs($usuario);
        $aviso = $this->aviso();
        $this->get('/notificaciones')->assertForbidden();
        $this->get('/notificaciones/'.$aviso->id)->assertForbidden();
        $this->post('/notificaciones/'.$aviso->id.'/reintentar', ['motivo' => 'Prueba'])->assertForbidden();
    }

    public function test_aviso_leido_entre_reserva_y_envio_no_se_envia(): void
    {
        $aviso = $this->aviso();
        Event::listen('eloquent.created: '.ComNotificacionIntento::class, function ($intento) use ($aviso) {
            if ($intento->resultado === ComNotificacionIntento::EN_PROCESO) {
                $aviso->marcarLeida();
            }
        });
        Mail::shouldReceive('mailer')->never();
        app(EnvioDeNotificaciones::class)->enviar($aviso->id);
        $this->assertSame('LEIDA', $aviso->fresh()->estado);
        $this->assertSame(ComNotificacionIntento::OMITIDO, $aviso->intentos()->first()->resultado);
    }

    public function test_programador_encola_solo_avisos_elegibles_y_respeta_lote(): void
    {
        config(['queue.default' => 'database', 'siges.notificaciones.lote' => 1]);
        Bus::fake();
        $this->aviso(['trx_incidencia_id' => null]);
        $primero = $this->aviso();
        $this->aviso();
        $this->artisan('siges:programar-notificaciones')->assertSuccessful();
        Bus::assertDispatchedTimes(EnviarNotificacion::class, 1);
        Bus::assertDispatched(EnviarNotificacion::class, fn ($tarea) => $tarea->notificacionId === $primero->id
            && $tarea->queue === 'notificaciones' && $tarea->afterCommit);
    }

    public function test_programador_no_envia_sin_cola_persistente(): void
    {
        Bus::fake();
        $this->aviso();
        foreach (['sync', 'null', 'deferred', 'background'] as $conexion) {
            config(['queue.default' => $conexion]);
            $this->artisan('siges:programar-notificaciones')->assertFailed();
        }
        Bus::assertNothingDispatched();
    }

    public function test_no_reintenta_una_reserva_reciente_aunque_haya_confirmacion(): void
    {
        $aviso = $this->aviso();
        $aviso->intentos()->create(['fecha_intento' => now(), 'exitoso' => false, 'resultado' => ComNotificacionIntento::EN_PROCESO]);
        $this->expectException(ValidationException::class);
        app(EnvioDeNotificaciones::class)->reprogramar($aviso, 'Revisado', true);
    }

    public function test_cancelacion_del_mailer_no_se_registra_como_enviada(): void
    {
        $aviso = $this->aviso();
        $mailer = Mockery::mock();
        Mail::shouldReceive('mailer')->once()->andReturn($mailer);
        $mailer->shouldReceive('to')->once()->andReturnSelf();
        $mailer->shouldReceive('send')->once()->andReturnNull();
        app(EnvioDeNotificaciones::class)->enviar($aviso->id);
        $this->assertSame('FALLIDA', $aviso->fresh()->estado);
        $this->assertSame(ComNotificacionIntento::INCIERTO, $aviso->intentos()->first()->resultado);
    }
}
