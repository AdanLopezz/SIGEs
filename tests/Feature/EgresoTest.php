<?php

namespace Tests\Feature;

use App\Models\PerAlumno;
use App\Models\PerPersona;
use App\Models\TrxProcesoEgreso;
use App\Models\TrxEgreso;
use App\Models\User;
use App\Support\RevisionEgreso;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EgresoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-16 12:00:00'));
        $this->withoutVite();
        // Solo tablas necesarias del esquema real, adaptadas a SQLite en memoria.
        $tablas = ['per_persona', 'users', 'cat_estado_alumno', 'aca_carrera', 'per_alumno', 'aca_turno',
            'aca_periodo_escolar', 'aca_grupo_escolar', 'aca_inscripcion', 'aca_asignatura', 'aca_grupo_asignatura',
            'aca_evaluacion', 'cat_tipo_evaluacion', 'trx_resultado_asignatura', 'aca_recursamiento', 'org_empresa', 'org_convenio',
            'trx_proceso_egreso', 'trx_egreso', 'sys_auditoria', 'sys_auditoria_detalle', 'per_credencial_acceso',
            'roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions', 'sys_configuracion'];
        $esquema = file_get_contents(database_path('schema/pgsql-schema.sql'));
        foreach ($tablas as $tabla) {
            preg_match('/CREATE TABLE public\.'.preg_quote($tabla, '/').' \((.*?)\n\);/s', $esquema, $coincidencia);
            $this->assertNotEmpty($coincidencia, $tabla);
            $columnas = preg_replace('/\bid bigint NOT NULL/', 'id integer primary key autoincrement', $coincidencia[1], 1);
            $columnas = preg_replace('/::(?:character varying|text|regclass)/', '', $columnas);
            $columnas = str_replace('timestamp(0) without time zone', 'datetime', $columnas);
            DB::statement("CREATE TABLE {$tabla} ({$columnas})");
        }
        $this->insertar('per_persona', ['id' => 1, 'curp' => PerPersona::CURP_SISTEMA]);
        $this->insertar('users', ['id' => 1, 'per_persona_id' => 1]);
        $this->insertar('cat_estado_alumno', ['id' => 1, 'nombre' => 'Activo', 'permite_inscripcion' => true]);
        $this->insertar('cat_estado_alumno', ['id' => 2, 'nombre' => 'Egresado', 'permite_inscripcion' => false]);
        $this->insertar('aca_carrera', ['id' => 1, 'total_semestres' => 6]);
        $this->insertar('per_alumno', ['id' => 1, 'per_persona_id' => 1, 'aca_carrera_id' => 1, 'cat_estado_alumno_id' => 1, 'fecha_ingreso' => '2023-08-01']);
        $this->insertar('aca_periodo_escolar', ['id' => 1, 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15', 'activo' => true]);
        $this->insertar('aca_grupo_escolar', ['id' => 1, 'aca_periodo_escolar_id' => 1, 'aca_carrera_id' => 1, 'semestre' => 6]);
        $this->insertar('aca_inscripcion', ['id' => 1, 'aca_grupo_escolar_id' => 1, 'per_alumno_id' => 1, 'estado' => 'INSCRITO', 'fecha_inscripcion' => '2026-08-01']);
        $this->insertar('aca_asignatura', ['id' => 1]);
        $this->insertar('aca_grupo_asignatura', ['id' => 1, 'aca_grupo_escolar_id' => 1, 'aca_asignatura_id' => 1]);
        $this->insertar('aca_evaluacion', ['id' => 1, 'aca_grupo_asignatura_id' => 1, 'cerrada' => true]);
        $this->insertar('trx_resultado_asignatura', ['id' => 1, 'aca_inscripcion_id' => 1, 'aca_grupo_asignatura_id' => 1, 'oportunidad' => 'ORDINARIO', 'estado' => 'APROBADA', 'calificacion' => 8]);
        $this->insertar('org_convenio', ['id' => 1, 'tipo' => 'GENERAL', 'estado' => 'VIGENTE', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31']);
        $this->insertar('org_empresa', ['id' => 1]);
        $this->insertar('permissions', ['id' => 1, 'name' => 'egreso.administrar', 'guard_name' => 'web']);
        foreach ([2 => 'Control Escolar', 3 => 'Vinculación', 4 => 'Docente', 5 => 'Administrador'] as $id => $rol) {
            $this->insertar('per_persona', ['id' => $id, 'curp' => 'PRUEBA'.$id]);
            $this->insertar('users', ['id' => $id, 'per_persona_id' => $id, 'email' => 'prueba'.$id.'@example.test']);
            $this->insertar('roles', ['id' => $id, 'name' => $rol, 'guard_name' => 'web']);
            DB::table('model_has_roles')->insert(['model_id' => $id, 'model_type' => User::class, 'role_id' => $id]);
            if ($id !== 4) {
                DB::table('role_has_permissions')->insert(['role_id' => $id, 'permission_id' => 1]);
            }
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function insertar(string $tabla, array $datos): void
    {
        foreach (DB::select("PRAGMA table_info({$tabla})") as $columna) {
            if ($columna->notnull && $columna->dflt_value === null && ! array_key_exists($columna->name, $datos)) {
                $datos[$columna->name] = preg_match('/int|numeric|boolean/', $columna->type) ? 1
                    : (str_contains($columna->type, 'date') || str_contains($columna->type, 'timestamp') ? '2026-01-01' : 'Prueba');
            }
        }
        DB::table($tabla)->insert($datos);
    }

    private function datosProceso(array $cambios = []): array
    {
        return $cambios + ['tipo' => 'SERVICIO_SOCIAL', 'org_convenio_id' => 1, 'estado' => 'EN_PROCESO',
            'horas_acreditadas' => 0, 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-10-30', 'motivo' => 'Revisión del expediente de prueba'];
    }

    public function test_candidatos_excluyen_semestres_previos_futuros_y_egresados(): void
    {
        $this->assertSame(1, RevisionEgreso::consulta([])->count());
        DB::table('aca_grupo_escolar')->update(['semestre' => 5]);
        $this->assertSame(0, RevisionEgreso::consulta([])->count());
        DB::table('aca_grupo_escolar')->update(['semestre' => 6]);
        DB::table('aca_periodo_escolar')->update(['fecha_inicio' => '2027-01-01']);
        $this->assertSame(0, RevisionEgreso::consulta([])->count());
        DB::table('aca_periodo_escolar')->update(['fecha_inicio' => '2026-08-01']);
        $this->insertar('trx_egreso', ['per_alumno_id' => 1]);
        $this->assertSame(0, RevisionEgreso::consulta([])->count());
        $this->assertSame(1, RevisionEgreso::consulta(['lista' => 'egresados'])->count());
    }

    public function test_revision_detecta_resultados_faltantes_y_evaluaciones_abiertas(): void
    {
        $alumno = PerAlumno::findOrFail(1);
        $this->assertCount(0, RevisionEgreso::academica($alumno)['pendientes']);
        DB::table('aca_evaluacion')->update(['cerrada' => false]);
        $this->assertCount(1, RevisionEgreso::academica($alumno)['pendientes']);
        DB::table('aca_evaluacion')->update(['cerrada' => true]);
        DB::table('trx_resultado_asignatura')->delete();
        $this->assertCount(1, RevisionEgreso::academica($alumno)['pendientes']);
    }

    public function test_revision_detecta_adeudos_y_su_regularizacion(): void
    {
        $alumno = PerAlumno::findOrFail(1);
        DB::table('trx_resultado_asignatura')->update(['estado' => 'REPROBADA', 'calificacion' => 5]);
        $this->assertSame(1, RevisionEgreso::academica($alumno)['adeudos']);
        DB::table('trx_resultado_asignatura')->update(['estado' => 'APROBADA', 'oportunidad' => 'EXTRAORDINARIO', 'calificacion' => 7]);
        $this->assertSame(0, RevisionEgreso::academica($alumno)['adeudos']);
    }

    public function test_proceso_se_actualiza_sin_duplicar_y_con_bitacora(): void
    {
        $alumno = PerAlumno::findOrFail(1);
        $proceso = TrxProcesoEgreso::guardarPara($alumno, $this->datosProceso());
        TrxProcesoEgreso::guardarPara($alumno, $this->datosProceso(['estado' => 'CONCLUIDO', 'horas_acreditadas' => 480]));
        $this->assertSame(1, $alumno->procesosEgreso()->count());
        $this->assertSame('CONCLUIDO', $proceso->fresh()->estado);
        $this->assertDatabaseHas('sys_auditoria', ['tabla_afectada' => 'trx_proceso_egreso', 'accion' => 'UPDATE']);
    }

    public function test_convenio_de_otro_tipo_no_admite_el_proceso(): void
    {
        DB::table('org_convenio')->update(['tipo' => 'DUAL']);
        $this->expectException(ValidationException::class);
        TrxProcesoEgreso::guardarPara(PerAlumno::findOrFail(1), $this->datosProceso());
    }

    public function test_fechas_fuera_del_convenio_no_se_guardan(): void
    {
        $this->expectException(ValidationException::class);
        TrxProcesoEgreso::guardarPara(PerAlumno::findOrFail(1), $this->datosProceso(['fecha_fin' => '2027-01-01']));
    }

    public function test_no_concluye_un_proceso_con_fecha_futura(): void
    {
        $this->expectException(ValidationException::class);
        TrxProcesoEgreso::guardarPara(PerAlumno::findOrFail(1), $this->datosProceso(['estado' => 'CONCLUIDO', 'horas_acreditadas' => 480, 'fecha_fin' => '2026-12-01']));
    }

    public function test_procesos_informativos_se_actualizan_despues_del_egreso(): void
    {
        $this->insertar('trx_egreso', ['per_alumno_id' => 1]);
        DB::table('per_alumno')->update(['cat_estado_alumno_id' => 2]);
        $proceso = TrxProcesoEgreso::guardarPara(PerAlumno::findOrFail(1), $this->datosProceso());
        $this->assertSame(1, $proceso->per_alumno_id);
    }

    private function datosEgreso(array $cambios = []): array
    {
        return $cambios + ['fecha_egreso' => '2026-11-16', 'promedio_general' => '8.75',
            'historial_verificado' => 1, 'motivo' => 'Historial oficial cotejado por Control Escolar'];
    }

    public function test_egreso_sin_procesos_cierra_inscripcion_desactiva_qr_y_conserva_historial(): void
    {
        $this->insertar('per_credencial_acceso', ['per_alumno_id' => 1, 'activa' => true]);
        $this->actingAs(User::findOrFail(2))->post('/egreso/1/confirmar', $this->datosEgreso())->assertSessionHasNoErrors()->assertRedirect(route('egreso.show', 1));
        $this->assertDatabaseHas('trx_egreso', ['per_alumno_id' => 1, 'promedio_general' => 8.75, 'certificado_entregado' => false, 'titulado' => false]);
        $this->assertDatabaseHas('aca_inscripcion', ['id' => 1, 'estado' => 'EGRESADO']);
        $this->assertDatabaseHas('per_alumno', ['id' => 1, 'cat_estado_alumno_id' => 2]);
        $this->assertDatabaseHas('per_credencial_acceso', ['per_alumno_id' => 1, 'activa' => false]);
        $this->assertDatabaseHas('sys_auditoria', ['tabla_afectada' => 'trx_egreso', 'user_id' => 2]);
        $this->assertSame(1, DB::table('trx_resultado_asignatura')->count());
        $this->assertSame(1, DB::table('aca_evaluacion')->count());
        $this->assertSame(0, RevisionEgreso::consulta([])->count());
        $this->assertSame(1, RevisionEgreso::consulta(['lista' => 'egresados', 'periodo' => 1])->count());
    }

    public function test_vinculacion_gestiona_procesos_pero_no_confirma_ni_cambia_documentacion(): void
    {
        $this->actingAs(User::findOrFail(3))->post('/egreso/1/procesos', $this->datosProceso())->assertSessionHasNoErrors()->assertRedirect();
        $this->post('/egreso/1/confirmar', $this->datosEgreso())->assertForbidden();
        $this->put('/egreso/1/documentacion', ['certificado_entregado' => 1, 'titulado' => 1, 'motivo' => 'Prueba'])->assertForbidden();
        $this->assertDatabaseCount('trx_egreso', 0);
    }

    public function test_docente_no_accede_a_consultas_exportaciones_o_procesos(): void
    {
        $this->actingAs(User::findOrFail(4));
        foreach (['/egreso', '/egreso/1', '/egreso/csv', '/egreso/imprimir', '/egreso/1/procesos/SERVICIO_SOCIAL'] as $ruta) {
            $this->get($ruta)->assertForbidden();
        }
        $this->post('/egreso/1/procesos', $this->datosProceso())->assertForbidden();
    }

    public function test_sin_confirmacion_del_historial_o_promedio_valido_no_egresa(): void
    {
        $this->actingAs(User::findOrFail(2));
        $this->post('/egreso/1/confirmar', $this->datosEgreso(['historial_verificado' => 0]))->assertSessionHasErrors('historial_verificado');
        $this->post('/egreso/1/confirmar', $this->datosEgreso(['promedio_general' => '10.50']))->assertSessionHasErrors('promedio_general');
        $this->post('/egreso/1/confirmar', $this->datosEgreso(['fecha_egreso' => '2026-12-01']))->assertSessionHasErrors('fecha_egreso');
        $this->assertDatabaseCount('trx_egreso', 0);
    }

    public function test_pendientes_academicos_bloquean_egreso_sin_cambiar_estado(): void
    {
        DB::table('trx_resultado_asignatura')->update(['estado' => 'REPROBADA', 'calificacion' => 5]);
        $this->actingAs(User::findOrFail(2))->post('/egreso/1/confirmar', $this->datosEgreso())->assertSessionHasErrors('accion');
        $this->assertDatabaseCount('trx_egreso', 0);
        $this->assertDatabaseHas('per_alumno', ['id' => 1, 'cat_estado_alumno_id' => 1]);
        $this->assertDatabaseHas('aca_inscripcion', ['id' => 1, 'estado' => 'INSCRITO']);
    }

    public function test_no_se_duplica_egreso_y_documentacion_no_modifica_promedio(): void
    {
        $this->actingAs(User::findOrFail(5))->post('/egreso/1/confirmar', $this->datosEgreso())->assertSessionHasNoErrors();
        $this->post('/egreso/1/confirmar', $this->datosEgreso())->assertSessionHasErrors('accion');
        $this->put('/egreso/1/documentacion', ['certificado_entregado' => 1, 'titulado' => 1, 'motivo' => 'Documentos cotejados'])->assertRedirect();
        $this->assertDatabaseCount('trx_egreso', 1);
        $this->assertDatabaseHas('trx_egreso', ['promedio_general' => 8.75, 'certificado_entregado' => true, 'titulado' => true]);
    }

    public function test_pantallas_y_exportaciones_se_renderizan_con_componentes_existentes(): void
    {
        $this->actingAs(User::findOrFail(2));
        $this->get('/egreso')->assertOk()->assertSee('tabla-siges', false)->assertSee('Descargar CSV');
        $this->get('/egreso/1')->assertOk()->assertSee('Confirmar egreso')->assertSee('form-seccion', false);
        $this->get('/egreso/1/procesos/SERVICIO_SOCIAL')->assertOk()->assertSee('Guardar proceso');
        $this->get('/egreso/imprimir')->assertOk()->assertSee('reporte-hoja', false);
        $this->get('/egreso/csv')->assertOk()->assertDownload('egreso-candidatos.csv');
    }

    public function test_fecha_de_egreso_no_precede_resultados_ni_inscripcion(): void
    {
        DB::table('trx_resultado_asignatura')->update(['fecha_cierre' => '2026-11-10']);
        $this->actingAs(User::findOrFail(2))->post('/egreso/1/confirmar', $this->datosEgreso(['fecha_egreso' => '2026-11-09']))
            ->assertSessionHasErrors('fecha_egreso');
        $this->assertDatabaseCount('trx_egreso', 0);
    }

    public function test_inscripcion_futura_impide_dejar_un_egresado_inscrito(): void
    {
        $this->insertar('aca_periodo_escolar', ['id' => 2, 'clave' => '2027-1', 'fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-06-30', 'activo' => false]);
        $this->insertar('aca_grupo_escolar', ['id' => 2, 'aca_periodo_escolar_id' => 2, 'aca_carrera_id' => 1, 'semestre' => 6]);
        $this->insertar('aca_inscripcion', ['id' => 2, 'aca_grupo_escolar_id' => 2, 'per_alumno_id' => 1, 'estado' => 'INSCRITO']);
        $this->actingAs(User::findOrFail(2))->post('/egreso/1/confirmar', $this->datosEgreso())->assertSessionHasErrors('accion');
        $this->assertDatabaseCount('trx_egreso', 0);
    }

    public function test_si_falla_el_cambio_de_estado_se_revierte_todo_el_egreso(): void
    {
        DB::table('cat_estado_alumno')->where('id', 2)->delete();
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        try {
            TrxEgreso::registrar(PerAlumno::findOrFail(1), today(), '8.75', 'Revisión oficial', User::findOrFail(2));
        } finally {
            $this->assertDatabaseCount('trx_egreso', 0);
            $this->assertDatabaseCount('sys_auditoria', 0);
            $this->assertDatabaseHas('aca_inscripcion', ['id' => 1, 'estado' => 'INSCRITO']);
        }
    }

    public function test_recursamiento_aprobado_salda_adeudo_sin_contarlo_dos_veces(): void
    {
        DB::table('trx_resultado_asignatura')->update(['estado' => 'REPROBADA', 'calificacion' => 5]);
        $this->insertar('aca_grupo_escolar', ['id' => 2, 'aca_periodo_escolar_id' => 1, 'aca_carrera_id' => 1, 'semestre' => 6]);
        $this->insertar('aca_grupo_asignatura', ['id' => 2, 'aca_grupo_escolar_id' => 2, 'aca_asignatura_id' => 1]);
        $this->insertar('aca_evaluacion', ['id' => 2, 'aca_grupo_asignatura_id' => 2, 'cerrada' => true]);
        $this->insertar('aca_recursamiento', ['trx_resultado_asignatura_id' => 1, 'aca_grupo_asignatura_id' => 2]);
        $this->insertar('trx_resultado_asignatura', ['id' => 2, 'aca_inscripcion_id' => 1, 'aca_grupo_asignatura_id' => 2, 'oportunidad' => 'RECURSAMIENTO', 'estado' => 'APROBADA', 'calificacion' => 8]);
        $this->assertSame(0, RevisionEgreso::academica(PerAlumno::findOrFail(1))['adeudos']);
        $this->actingAs(User::findOrFail(2))->post('/egreso/1/confirmar', $this->datosEgreso())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('trx_egreso', 1);
    }

    public function test_egresado_conserva_consulta_de_su_inscripcion_historica(): void
    {
        $this->actingAs(User::findOrFail(2))->post('/egreso/1/confirmar', $this->datosEgreso())->assertSessionHasNoErrors();
        $alumno = PerAlumno::findOrFail(1);
        $this->assertSame('EGRESADO', $alumno->inscripcionDelPeriodo(1)->estado);
        $this->get('/egreso/1')->assertOk()->assertSee('Egreso registrado')->assertSee('Actualizar documentación');
        $this->get('/egreso?lista=egresados')->assertOk()->assertSee('8.75');
    }
}
