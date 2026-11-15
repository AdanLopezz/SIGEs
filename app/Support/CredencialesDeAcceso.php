<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Usuario y contraseña temporal que genera el sistema al crear una cuenta.
 * - Usuario: primernombre.primerapellido + 4 dígitos + @dominio (config siges.cuentas.dominio), sin acentos ni ñ.
 *   Es único en users.email y en per_alumno.email_institucional (el alumno usa el mismo valor para los dos).
 * - Contraseña: legible (sin 0/O/o, 1/l/I/i) y con la política de SIGEs (mayúscula, minúscula y número).
 * El dominio no es nuestro: nunca se envía correo a estas direcciones (User::routeNotificationForMail).
 */
class CredencialesDeAcceso
{
    private const MAYUSCULAS = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const MINUSCULAS = 'abcdefghjkmnpqrstuvwxyz';

    private const DIGITOS = '23456789';

    public const LONGITUD_CONTRASENA = 10;

    private const INTENTOS = 50;

    public static function dominio(): string
    {
        return Str::lower(trim((string) config('siges.cuentas.dominio')));
    }

    // "María José" + "De la Cruz" → "maria.delacruz".
    public static function base(string $nombre, string $primerApellido): string
    {
        $limpiar = fn (string $texto) => preg_replace('/[^a-z]/', '', Str::lower(Str::ascii($texto)));
        $nombre = $limpiar(Str::before(trim($nombre).' ', ' '));
        $apellido = $limpiar($primerApellido);

        return implode('.', array_filter([$nombre, $apellido])) ?: 'usuario';
    }

    public static function usuario(string $nombre, string $primerApellido): string
    {
        $base = static::base($nombre, $primerApellido);
        for ($intento = 0; $intento < self::INTENTOS; $intento++) {
            $usuario = $base.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT).'@'.static::dominio();
            if (static::disponible($usuario)) {
                return $usuario;
            }
        }

        throw ValidationException::withMessages(['accion' => 'No se pudo generar un usuario disponible. Inténtalo de nuevo.']);
    }

    // Conserva el usuario propuesto (el que se vio en el formulario) si corresponde al nombre y está libre; si no, genera otro.
    public static function usuarioPara(string $nombre, string $primerApellido, mixed $propuesto): string
    {
        $propuesto = is_string($propuesto) ? Str::lower(trim($propuesto)) : '';

        return static::esGenerado($propuesto, $nombre, $primerApellido) && static::disponible($propuesto)
            ? $propuesto
            : static::usuario($nombre, $primerApellido);
    }

    public static function esGenerado(string $usuario, string $nombre, string $primerApellido): bool
    {
        $patron = '/^'.preg_quote(static::base($nombre, $primerApellido), '/').'\d{4}@'.preg_quote(static::dominio(), '/').'$/';

        return preg_match($patron, $usuario) === 1;
    }

    public static function disponible(string $usuario): bool
    {
        $usuario = Str::lower($usuario);

        return DB::table('users')->whereRaw('lower(email) = ?', [$usuario])->doesntExist()
            && DB::table('per_alumno')->whereRaw('lower(email_institucional) = ?', [$usuario])->doesntExist();
    }

    public static function contrasena(): string
    {
        $todos = self::MAYUSCULAS.self::MINUSCULAS.self::DIGITOS;
        $caracteres = [static::uno(self::MAYUSCULAS), static::uno(self::MINUSCULAS), static::uno(self::DIGITOS)];
        while (count($caracteres) < self::LONGITUD_CONTRASENA) {
            $caracteres[] = static::uno($todos);
        }
        // Mezcla Fisher-Yates con random_int (criptográfico).
        for ($i = count($caracteres) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$caracteres[$i], $caracteres[$j]] = [$caracteres[$j], $caracteres[$i]];
        }

        return implode('', $caracteres);
    }

    private static function uno(string $alfabeto): string
    {
        return $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }
}
