<?php

namespace App\Console\Commands;

use App\Models\PerPersona;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\PermissionRegistrar;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CrearAdministrador extends Command
{
    protected $signature = 'siges:crear-admin
                            {--asignar= : Correo de un usuario existente al que solo se le asigna el rol Administrador}';

    protected $description = 'Crea el primer administrador de SIGEs (persona + cuenta + rol)';

    public function handle(): int
    {
        // Modo 1: solo asignar el rol a una cuenta que ya existe
        if ($correo = $this->option('asignar')) {
            $user = User::where('email', mb_strtolower(trim($correo)))->first();

            if (! $user) {
                $this->error("No existe una cuenta con el correo {$correo}.");

                return self::FAILURE;
            }

            $user->assignRole('Administrador');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->info("Listo: {$user->email} ahora es Administrador.");
            return self::SUCCESS;
        }

        // Modo 2: crear persona + cuenta + rol
        $this->info('Alta del administrador de SIGEs. Los campos con * son obligatorios.');

        $curp = mb_strtoupper(text('CURP *', required: true, validate: fn ($v) =>
        preg_match('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/', mb_strtoupper(trim($v)))
            ? null : 'La CURP no tiene un formato válido (18 caracteres).'));

        $persona = PerPersona::where('curp', $curp)->first();

        if ($persona?->user) {
            $this->error("Esa persona ya tiene la cuenta {$persona->user->email}.");
            $this->line("Para hacerla administradora usa: php artisan siges:crear-admin --asignar={$persona->user->email}");
            return self::FAILURE;
        }

        if ($persona) {
            $this->info("Se encontró a {$persona->nombre_completo}: se usarán sus datos personales.");
            $datos = [];
        } else {
            $datos = [
                'curp'             => $curp,
                'nombre'           => text('Nombre(s) *', required: true),
                'primer_apellido'  => text('Primer apellido *', required: true),

                'segundo_apellido' => text('Segundo apellido (Enter si no tiene)', default: ''),
                'email_personal'   => text('Correo personal *', required: true),
                'telefono'         => text('Teléfono (10 dígitos) *', required: true),
                'calle_numero'     => text('Calle y número *', required: true),
                'colonia'          => text('Colonia *', required: true),
                'municipio'        => text('Municipio *', required: true),
                'codigo_postal'    => text('Código postal *', required: true),
            ];
        }

        $email = mb_strtolower(trim(text('Correo de acceso (con el que iniciará sesión) *', required: true)));
        $clave = password('Contraseña *', required: true);
        $clave2 = password('Confirmar contraseña *', required: true);

        $validator = Validator::make(
            array_merge($datos, ['email' => $email, 'password' => $clave, 'password_confirmation' => $clave2]),
            [
                'email_personal' => ['sometimes', 'email'],
                'telefono'       => ['sometimes', 'digits:10'],
                'codigo_postal'  => ['sometimes', 'digits:5'],
                'email'          => ['required', 'email', 'unique:users,email'],
                'password'       => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            ],
            [],
            ['email' => 'correo de acceso', 'password' => 'contraseña', 'telefono' => 'teléfono', 'codigo_postal' => 'código postal'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;

        }

        if (! confirm("¿Crear la cuenta {$email} con el rol Administrador?", default: true)) {
            $this->warn('Cancelado. No se guardó nada.');
            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($persona, $datos, $email, $clave) {
            $persona ??= PerPersona::create($datos);

            $user = User::create([
                'per_persona_id' => $persona->id,
                'name'           => $persona->nombre_completo,
                'email'          => $email,
                'password'       => Hash::make($clave),
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole('Administrador');

            return $user;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Administrador creado: {$user->name} ({$user->email}). Ya puede iniciar sesión.");
        return self::SUCCESS;
    }
}
