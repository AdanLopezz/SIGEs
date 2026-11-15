<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Menu
{
    public static function para(User $usuario): Collection
    {
        // Una ruta que aparece en dos secciones (p. ej. Avisos en "Mi escuela" y "Mis hijos") se muestra una sola vez.
        $mostradas = [];

        return collect(config('menu', []))->map(function ($seccion) use ($usuario, &$mostradas) {
            $seccion['opciones'] = collect($seccion['opciones'])->filter(function ($opcion) use ($usuario, &$mostradas) {
                $visible = Route::has($opcion['ruta']) && ! isset($mostradas[$opcion['ruta']])
                    && static::cumplePermiso($usuario, $opcion['permiso'] ?? null)
                    && static::cumpleRol($usuario, $opcion['rol'] ?? null);
                if ($visible) {
                    $mostradas[$opcion['ruta']] = true;
                }

                return $visible;
            })->map(fn ($opcion) => $opcion + ['patron' => str_contains($opcion['ruta'], '.')
                ? Str::beforeLast($opcion['ruta'], '.').'.*' : $opcion['ruta']])->values();

            return $seccion;
        })->filter(fn ($seccion) => $seccion['opciones']->isNotEmpty())->values();
    }

    public static function modulosPara(User $usuario): Collection
    {
        return static::para($usuario)->flatMap(fn ($seccion) => $seccion['opciones']->map(fn ($opcion) =>
            $opcion + ['seccion' => $seccion['seccion']]))->reject(fn ($opcion) => $opcion['ruta'] === 'dashboard')->values();
    }

    // permiso: null (cualquiera), un permiso o una lista (basta con uno).
    private static function cumplePermiso(User $usuario, string|array|null $permiso): bool
    {
        return $permiso === null || $usuario->canAny((array) $permiso);
    }

    // rol: null (cualquiera), un rol o una lista (basta con uno).
    private static function cumpleRol(User $usuario, string|array|null $rol): bool
    {
        return $rol === null || $usuario->hasAnyRole((array) $rol);
    }
}
