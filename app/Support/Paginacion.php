<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcreto;
use Illuminate\Support\Collection;

/**
 * Registros por página de los listados (config siges.paginacion).
 * - ?por_pagina= válido: se usa y se recuerda en session('por_pagina.{pantalla}') (por usuario y por pantalla).
 * - ?por_pagina= con otro valor: se usa el valor por defecto y se olvida lo guardado.
 * - sin el parámetro: lo guardado (si sigue siendo una opción) o el valor por defecto.
 * La pantalla es el nombre de la ruta (alumnos.index, mis-hijos.permisos.index…).
 * Todo listado paginado usa paginar() (o porPagina()) y, en su tabla-pie, <x-por-pagina>.
 */
class Paginacion
{
    public const PARAMETRO = 'por_pagina';

    /** @return int[] */
    public static function opciones(): array
    {
        $opciones = array_values(array_unique(array_filter(array_map('intval', (array) config('siges.paginacion.opciones', [10, 25, 50, 100])), fn (int $valor) => $valor > 0)));
        sort($opciones);

        return $opciones ?: [25];
    }

    public static function porDefecto(): int
    {
        $valor = (int) config('siges.paginacion.por_defecto', 25);

        return in_array($valor, static::opciones(), true) ? $valor : static::opciones()[0];
    }

    public static function porPagina(Request $solicitud, ?string $pantalla = null): int
    {
        // La edición de un catálogo muestra la misma lista que su index: comparten lo elegido.
        $pantalla ??= preg_replace('/\.edit$/', '.index', $solicitud->route()?->getName() ?? $solicitud->path());
        $clave = 'por_pagina.'.$pantalla;
        $pedido = $solicitud->query(self::PARAMETRO);
        $sesion = $solicitud->hasSession() ? $solicitud->session() : null;
        if ($pedido !== null) {
            $valor = is_string($pedido) && ctype_digit($pedido) ? (int) $pedido : null;
            if ($valor !== null && in_array($valor, static::opciones(), true)) {
                $sesion?->put($clave, $valor);

                return $valor;
            }
            $sesion?->forget($clave);

            return static::porDefecto();
        }
        $guardado = $sesion?->get($clave);

        return in_array($guardado, static::opciones(), true) ? $guardado : static::porDefecto();
    }

    /**
     * paginate() con los registros por página del usuario, conservando los filtros (withQueryString).
     * Si la página pedida ya no existe (p. ej. después de pasar a 100 por página), redirige a la última, con los mismos filtros.
     */
    public static function paginar(Builder $consulta, Request $solicitud, ?string $pantalla = null): LengthAwarePaginator
    {
        $paginador = $consulta->paginate(static::porPagina($solicitud, $pantalla))->withQueryString();
        if ($paginador->total() > 0 && $paginador->currentPage() > $paginador->lastPage()) {
            throw new HttpResponseException(redirect($paginador->url($paginador->lastPage())));
        }

        return $paginador;
    }

    // Lo mismo que paginar(), para listas que no salen de una consulta (p. ej. el asistente "Cambio de periodo").
    public static function paginarColeccion(Collection $elementos, Request $solicitud, ?string $pantalla = null): LengthAwarePaginator
    {
        $porPagina = static::porPagina($solicitud, $pantalla);
        $pagina = LengthAwarePaginatorConcreto::resolveCurrentPage();
        $paginador = (new LengthAwarePaginatorConcreto($elementos->forPage($pagina, $porPagina)->values(), $elementos->count(), $porPagina, $pagina,
            ['path' => $solicitud->url(), 'pageName' => 'page']))->withQueryString();
        if ($paginador->total() > 0 && $paginador->currentPage() > $paginador->lastPage()) {
            throw new HttpResponseException(redirect($paginador->url($paginador->lastPage())));
        }

        return $paginador;
    }
}
