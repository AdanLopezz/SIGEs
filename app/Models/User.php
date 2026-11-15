<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use App\Support\CredencialesDeAcceso;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable
{
    use Auditable, HasFactory, Notifiable;

    // Los cambios de roles se registran en la bitácora (model_has_roles) desde estos envoltorios.
    use HasRoles {
        syncRoles as private syncRolesSinBitacora;
        assignRole as private assignRoleSinBitacora;
        removeRole as private removeRoleSinBitacora;
    }

    private bool $registrandoRoles = false;

    public const ROLES_CONTROL_ESCOLAR = ['Alumno', 'Padre de familia'];

    public const CUENTA_CREADA = 'creada';

    public const ROL_AGREGADO = 'rol_agregado';

    public const SIN_CAMBIOS = 'sin_cambios';

    public ?string $resultado = null;

    protected $fillable = ['per_persona_id', 'name', 'email', 'password'];

    // password_temporal va cifrada (Crypt) y nunca se serializa ni se registra en la bitácora.
    protected $hidden = ['password', 'password_temporal', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_temporal' => 'encrypted',
            'debe_cambiar_password' => 'boolean',
        ];
    }
    public function persona(): BelongsTo
    {
        return $this->belongsTo(PerPersona::class, 'per_persona_id');
    }

    public function syncRoles(...$roles)
    {
        return $this->registrarRoles(fn () => $this->syncRolesSinBitacora(...$roles));
    }

    public function assignRole(...$roles)
    {
        return $this->registrarRoles(fn () => $this->assignRoleSinBitacora(...$roles));
    }

    public function removeRole(...$role)
    {
        return $this->registrarRoles(fn () => $this->removeRoleSinBitacora(...$role));
    }

    // syncRoles llama por dentro a removeRole y assignRole: solo la llamada exterior registra el cambio.
    private function registrarRoles(callable $cambio)
    {
        if ($this->registrandoRoles || ! $this->exists) {
            return $cambio();
        }
        $this->registrandoRoles = true;
        try {
            return DB::transaction(function () use ($cambio) {
                $antes = $this->roles()->orderBy('name')->pluck('name')->all();
                $resultado = $cambio();
                $despues = $this->roles()->orderBy('name')->pluck('name')->all();
                if ($antes !== $despues) {
                    $accion = $antes === [] ? 'INSERT' : ($despues === [] ? 'DELETE' : 'UPDATE');
                    Auditoria::registrar('model_has_roles', $this->id, $accion, ['roles' => [implode(', ', $antes), implode(', ', $despues)]]);
                }

                return $resultado;
            });
        } finally {
            $this->registrandoRoles = false;
        }
    }

    // Los correos a una cuenta van al correo personal de la persona, nunca a users.email (el dominio de los usuarios no es nuestro).
    public function routeNotificationForMail(): ?string
    {
        return $this->persona?->email_personal;
    }

    // Si el usuario cambia su contraseña (con o sin sesión), el cambio se le atribuye a él.
    protected function contextoAuditoria(string $accion, array $campos): array
    {
        $deContrasena = ['password', 'password_temporal', 'debe_cambiar_password'];
        if ($accion === 'UPDATE' && isset($campos['password']) && array_diff(array_keys($campos), $deContrasena) === []) {
            return ['motivo' => Auditoria::motivoActual() ?? 'Cambio de contraseña', 'usuario' => auth()->id() ?? $this->id];
        }

        return [];
    }

    public static function rolesAdministrables()
    {
        return Role::where('guard_name', 'web')->whereNotIn('name', self::ROLES_CONTROL_ESCOLAR)->orderBy('name')->pluck('name');
    }

    public function tieneAcceso(): bool
    {
        return $this->roles()->exists();
    }

    public function esSistema(): bool
    {
        return $this->persona?->curp === PerPersona::CURP_SISTEMA;
    }

    public function esUltimoAdministrador(): bool
    {
        return $this->hasRole('Administrador', 'web')
            && static::visibles()->role('Administrador', 'web')->whereKeyNot($this->id)->doesntExist();
    }

    public function quitarAcceso(): void
    {
        DB::transaction(function () {
            // Serializa los cambios de roles del módulo para proteger al último administrador.
            Role::where('name', 'Administrador')->where('guard_name', 'web')->lockForUpdate()->first();
            $this->refresh();
            abort_if($this->esSistema(), 404);
            if ($this->is(auth()->user())) {
                throw ValidationException::withMessages(['accion' => 'No puedes quitarte el acceso a ti mismo.']);
            }
            if ($this->esUltimoAdministrador()) {
                throw ValidationException::withMessages(['accion' => 'Debe quedar al menos un administrador en el sistema.']);
            }
            $this->syncRoles([]);
        });
    }

    public static function crearPersonal(array $datos): self
    {
        return DB::transaction(function () use ($datos) {
            abort_if($datos['curp'] === PerPersona::CURP_SISTEMA, 404);
            $persona = PerPersona::where('curp', $datos['curp'])->lockForUpdate()->first();
            if ($persona?->user()->exists()) {
                throw ValidationException::withMessages(['curp' => 'Esta persona ya tiene una cuenta en el sistema.']);
            }
            $persona = PerPersona::updateOrCreate(['curp' => $datos['curp']], PerPersona::datosDe($datos));
            $persona->personal()->updateOrCreate(['per_persona_id' => $persona->id], PerPersonal::datosDe($datos));

            return static::crearParaPersona($persona, $datos['email'], array_values(array_diff($datos['roles'], self::ROLES_CONTROL_ESCOLAR)));
        });
    }

    // Una cuenta por persona: si ya tiene una, solo se le agregan los roles que le falten (y conserva su usuario y contraseña).
    // Una cuenta nueva recibe una contraseña temporal (ver datosDeAcceso) y debe cambiarla al primer ingreso.
    public static function crearParaPersona(PerPersona $persona, string $email, array $roles): self
    {
        abort_if($persona->curp === PerPersona::CURP_SISTEMA, 404);

        return DB::transaction(function () use ($persona, $email, $roles) {
            $usuario = static::where('per_persona_id', $persona->id)->lockForUpdate()->first();
            if ($usuario) {
                $faltantes = array_values(array_diff($roles, $usuario->getRoleNames()->all()));
                if ($faltantes) {
                    $usuario->assignRole($faltantes);
                }
                $usuario->resultado = $faltantes ? self::ROL_AGREGADO : self::SIN_CAMBIOS;

                return $usuario;
            }
            $email = Str::lower(trim($email));
            if (static::whereRaw('lower(email) = ?', [$email])->exists()) {
                throw ValidationException::withMessages(['accion' => "El usuario {$email} ya lo usa otra cuenta."]);
            }
            // Un solo INSERT (también en la bitácora): email_verified_at y la contraseña temporal no son asignables en masa.
            $usuario = new static([
                'per_persona_id' => $persona->id,
                'name' => $persona->nombre_completo,
                'email' => $email,
            ]);
            $usuario->asignarContrasenaTemporal();
            $usuario->forceFill(['email_verified_at' => now()])->save();
            $usuario->syncRoles($roles);
            $usuario->resultado = self::CUENTA_CREADA;

            return $usuario;
        });
    }

    // Alumnos y padres de familia (Control Escolar): crea la cuenta o agrega el rol.
    public static function darAccesoControlEscolar(PerPersona $persona, string $email, string $rol): self
    {
        abort_unless(in_array($rol, self::ROLES_CONTROL_ESCOLAR, true), 404);

        return static::crearParaPersona($persona, $email, [$rol]);
    }

    /**
     * "¿Olvidaste tu contraseña?": busca por usuario (users.email) o por correo personal y envía un enlace por cuenta,
     * siempre al correo personal (routeNotificationForMail). No informa si encontró algo.
     */
    public static function enviarEnlacesDeRecuperacion(string $texto): void
    {
        $texto = Str::lower(trim($texto));
        $cuentas = static::visibles()->where(fn (Builder $usuarios) => $usuarios->whereRaw('lower(email) = ?', [$texto])
            ->orWhereHas('persona', fn (Builder $personas) => $personas->whereRaw('lower(email_personal) = ?', [$texto])))->get();
        foreach ($cuentas as $cuenta) {
            try {
                // Si ya se envió uno hace poco, el broker no envía otro (y no se avisa).
                Password::broker()->sendResetLink(['email' => $cuenta->email]);
            } catch (\Throwable $excepcion) {
                report($excepcion);
            }
        }
    }

    // Contraseña temporal: el hash en password, la misma cifrada en password_temporal y el cambio obligatorio. No guarda.
    private function asignarContrasenaTemporal(): void
    {
        $temporal = CredencialesDeAcceso::contrasena();
        $this->forceFill(['password' => $temporal, 'password_temporal' => $temporal, 'debe_cambiar_password' => true]);
        $this->setRememberToken(Str::random(60));
    }

    /**
     * Contraseña temporal para mostrarla a quien entrega la cuenta.
     * ['valor' => texto o null, 'ilegible' => true si existe pero no se pudo descifrar (cambió APP_KEY)].
     */
    public function datosDeAcceso(): array
    {
        if ($this->getRawOriginal('password_temporal') === null) {
            return ['valor' => null, 'ilegible' => false];
        }
        try {
            return ['valor' => $this->password_temporal, 'ilegible' => false];
        } catch (DecryptException) {
            return ['valor' => null, 'ilegible' => true];
        }
    }

    // Cada vez que se muestran los datos de acceso queda en la bitácora, sin el valor.
    public function registrarConsultaDeAcceso(bool $impresion = false): void
    {
        Auditoria::registrar('users', $this->id, 'SELECT', ['datos_acceso' => ['', $impresion ? 'Impresos' : 'Consultados']],
            $impresion ? 'Impresión de los datos de acceso' : 'Consulta de los datos de acceso');
    }

    // Desde la ficha del alumno (Control Escolar) solo se administran cuentas que son únicamente de Alumno o Padre de familia.
    public function esSoloDeAlumnoOPadre(): bool
    {
        return $this->getRoleNames()->diff(self::ROLES_CONTROL_ESCOLAR)->isEmpty();
    }

    // Otra contraseña temporal (el motivo lo fija el controlador). Cierra todas las sesiones de la cuenta.
    public function generarNuevaContrasena(): void
    {
        DB::transaction(function () {
            static::whereKey($this->id)->lockForUpdate()->first();
            $this->refresh();
            abort_if($this->esSistema(), 404);
            if ($this->is(auth()->user())) {
                throw ValidationException::withMessages(['accion' => 'No puedes generar una contraseña temporal para tu propia cuenta. Usa «Cambiar mi contraseña».']);
            }
            $this->asignarContrasenaTemporal();
            $this->save();
            $this->cerrarSesiones();
        });
    }

    // El usuario establece su propia contraseña: se borra la temporal y se cierran sus demás sesiones.
    public function cambiarContrasena(string $nueva, ?string $sesionActual = null): void
    {
        DB::transaction(function () use ($nueva, $sesionActual) {
            $this->forceFill(['password' => $nueva, 'password_temporal' => null, 'debe_cambiar_password' => false]);
            $this->setRememberToken(Str::random(60));
            $this->save();
            $this->cerrarSesiones($sesionActual);
        });
    }

    // sessions no se audita: es la única tabla en la que se borra en masa (las sesiones de una sola cuenta).
    private function cerrarSesiones(?string $excepto = null): void
    {
        DB::table('sessions')->where('user_id', $this->id)->when($excepto, fn ($consulta) => $consulta->where('id', '!=', $excepto))->delete();
    }

    public function actualizarDatos(array $datos): void
    {
        DB::transaction(function () use ($datos) {
            Role::where('name', 'Administrador')->where('guard_name', 'web')->lockForUpdate()->first();
            $this->refresh();
            abort_if($this->esSistema(), 404);
            if ($this->hasRole('Administrador', 'web') && ! in_array('Administrador', $datos['roles'], true)) {
                if ($this->is(auth()->user())) {
                    throw ValidationException::withMessages(['roles' => 'No puedes quitarte el rol de Administrador a ti mismo.']);
                }
                if ($this->esUltimoAdministrador()) {
                    throw ValidationException::withMessages(['roles' => 'Debe quedar al menos un administrador en el sistema.']);
                }
            }
            $persona = $this->persona;
            $persona->update(PerPersona::datosDe(array_diff_key($datos, ['curp' => true])));
            if (filled($datos['numero_empleado'] ?? null)) {
                $persona->personal()->updateOrCreate(['per_persona_id' => $persona->id], PerPersonal::datosDe($datos));
            }
            $this->update(['name' => $persona->nombre_completo, 'email' => $datos['email']]);
            $conservados = $this->getRoleNames()->intersect(self::ROLES_CONTROL_ESCOLAR)->all();
            $this->syncRoles(array_unique(array_merge($conservados, array_diff($datos['roles'], self::ROLES_CONTROL_ESCOLAR))));
        });
    }

    public function scopeVisibles(Builder $consulta): Builder
    {
        return $consulta->whereHas('persona', fn (Builder $personas) => $personas->where('curp', '!=', PerPersona::CURP_SISTEMA));
    }

    public function scopeBuscar(Builder $consulta, ?string $texto): Builder
    {
        if (! filled($texto)) {
            return $consulta;
        }
        $patron = '%'.trim($texto).'%';

        return $consulta->where(fn (Builder $usuarios) => $usuarios->where('email', 'ilike', $patron)
            ->orWhereHas('persona', fn (Builder $personas) => $personas->where('curp', 'ilike', $patron)
                ->orWhereRaw("concat_ws(' ', nombre, primer_apellido, segundo_apellido) ILIKE ?", [$patron])));
    }

    public function scopeConRol(Builder $consulta, ?string $rol): Builder
    {
        return filled($rol) ? $consulta->role($rol, 'web') : $consulta;
    }

    public function scopeAcceso(Builder $consulta, ?string $acceso): Builder
    {
        return match ($acceso) {
            'con' => $consulta->has('roles'),
            'sin' => $consulta->doesntHave('roles'),
            default => $consulta,
        };
    }
}
