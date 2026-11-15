<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\EliminaSiNoEstaEnUso;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcaPeriodoEscolar extends Model
{
    use Auditable;

    use EliminaSiNoEstaEnUso { eliminar as private eliminarSinReferencias; }

    protected $fillable = ['clave', 'fecha_inicio', 'fecha_fin'];

    protected $attributes = ['activo' => false];

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('fecha_inicio')->orderByDesc('id');
    }

    public static function seEncima(string $inicio, string $fin, ?int $ignorar = null): bool
    {
        return static::when($ignorar, fn ($consulta) => $consulta->whereKeyNot($ignorar))
            ->where('fecha_inicio', '<=', $fin)->where('fecha_fin', '>=', $inicio)->exists();
    }

    private static function bloquearCambios(): void
    {
        // Serializa los cambios incluso cuando todavía no hay periodos.
        DB::select('SELECT pg_advisory_xact_lock(17423, 1)');
    }

    public function guardar(array $datos): void
    {
        DB::transaction(function () use ($datos) {
            static::bloquearCambios();
            if (static::seEncima($datos['fecha_inicio'], $datos['fecha_fin'], $this->id)) {
                throw ValidationException::withMessages(['fecha_fin' => 'Las fechas se enciman con otro periodo escolar.']);
            }
            $this->fill($datos)->save();
        });
    }

    public function activar(): void
    {
        DB::transaction(function () {
            static::bloquearCambios();
            $this->refresh();
            Auditoria::motivoPorDefecto("Activación del periodo {$this->clave}");
            // Uno por uno (no UPDATE masivo) para que cada cambio quede en la bitácora.
            static::where('activo', true)->whereKeyNot($this->getKey())->get()
                ->each(fn (self $periodo) => $periodo->forceFill(['activo' => false])->save());
            $this->forceFill(['activo' => true])->save();
            $this->refresh();
        });
    }

    public function eliminar(): bool
    {
        return DB::transaction(function () {
            static::bloquearCambios();
            $this->refresh();
            if ($this->activo) {
                throw ValidationException::withMessages(['accion' => 'No se puede eliminar el periodo activo.']);
            }

            return $this->eliminarSinReferencias();
        });
    }

    protected $table = 'aca_periodo_escolar';

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'fecha_inicio' => 'date', 'fecha_fin' => 'date'];
    }

    public static function activo(): ?self
    {
        return static::where('activo', true)->orderByDesc('fecha_inicio')->first();
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(AcaGrupoEscolar::class, 'aca_periodo_escolar_id');
    }

    private const MESES = [1 => 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

    // "AGOSTO - DICIEMBRE 2026"; si cambia de año, "AGOSTO 2026 - ENERO 2027"; en un mismo mes, "AGOSTO 2026".
    public function getNombreVisibleAttribute(): string
    {
        $inicio = $this->fecha_inicio;
        $fin = $this->fecha_fin;
        if (! $inicio || ! $fin) {
            return (string) $this->clave;
        }
        [$mesInicio, $mesFin] = [self::MESES[$inicio->month], self::MESES[$fin->month]];

        return match (true) {
            $inicio->year !== $fin->year => "{$mesInicio} {$inicio->year} - {$mesFin} {$fin->year}",
            $inicio->month === $fin->month => "{$mesInicio} {$inicio->year}",
            default => "{$mesInicio} - {$mesFin} {$fin->year}",
        };
    }
}
