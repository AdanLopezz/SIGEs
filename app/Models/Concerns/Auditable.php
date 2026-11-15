<?php

namespace App\Models\Concerns;

use App\Support\Auditoria;
use Illuminate\Support\Facades\DB;

/**
 * Registra en la bitácora cada alta, edición y eliminación del modelo.
 * Opciones del modelo:
 *   protected array $auditarAcciones = ['INSERT'];        // por omisión las tres
 *   protected array $camposSensibles = ['nss', ...];      // se registra que cambió, sin su valor
 *   protected function contextoAuditoria(string $accion, array $campos): array  // ['motivo' => ?, 'usuario' => ?]
 */
trait Auditable
{
    // Estos campos nunca se guardan con su valor.
    private static array $camposOcultos = ['password', 'password_temporal', 'remember_token', 'token_hash'];

    private static array $camposOmitidos = ['id', 'created_at', 'updated_at'];

    public static function bootAuditable(): void
    {
        static::created(fn ($modelo) => $modelo->auditar('INSERT'));
        static::updated(fn ($modelo) => $modelo->auditar('UPDATE'));
        static::deleted(fn ($modelo) => $modelo->auditar('DELETE'));
    }

    // El cambio y su registro se confirman o se revierten juntos.
    public function save(array $options = [])
    {
        return DB::transaction(fn () => parent::save($options));
    }

    public function delete()
    {
        return DB::transaction(fn () => parent::delete());
    }

    protected function auditar(string $accion): void
    {
        $acciones = property_exists($this, 'auditarAcciones') ? $this->auditarAcciones : ['INSERT', 'UPDATE', 'DELETE'];
        if (! in_array($accion, $acciones, true)) {
            return;
        }
        $campos = $this->camposAuditados($accion);
        // Un UPDATE sin cambios que registrar (por ejemplo, solo remember_token) no deja rastro.
        if ($accion === 'UPDATE' && $campos === []) {
            return;
        }
        $contexto = $this->contextoAuditoria($accion, $campos);
        Auditoria::registrar($this->getTable(), (int) $this->getKey(), $accion, $campos, $contexto['motivo'] ?? null, $contexto['usuario'] ?? null);
    }

    protected function contextoAuditoria(string $accion, array $campos): array
    {
        return [];
    }

    private function camposAuditados(string $accion): array
    {
        $valores = $accion === 'UPDATE' ? $this->getChanges() : $this->getAttributes();
        $sensibles = property_exists($this, 'camposSensibles') ? $this->camposSensibles : [];
        $campos = [];
        foreach ($valores as $campo => $valor) {
            if (in_array($campo, self::$camposOmitidos, true) || $campo === $this->getKeyName()) {
                continue;
            }
            // Los campos con cast "date" se guardan como Y-m-d 00:00:00; en la bitácora basta la fecha.
            if ($this->hasCast($campo, ['date', 'immutable_date'])) {
                $valor = $valor === null ? null : substr((string) $valor, 0, 10);
            }
            if (in_array($campo, self::$camposOcultos, true)) {
                // De la contraseña solo se registra que cambió.
                if ($campo === 'password' && $accion === 'UPDATE') {
                    $campos[$campo] = ['', '(modificada)'];
                }
                // De la contraseña temporal (cifrada) solo se registra que se generó o se eliminó.
                if ($campo === 'password_temporal' && $accion !== 'DELETE' && ($accion === 'UPDATE' || $valor !== null)) {
                    $campos[$campo] = ['', $valor === null ? '(eliminada)' : '(generada)'];
                }
                continue;
            }
            $campos[$campo] = match (true) {
                in_array($campo, $sensibles, true) => ['', match ($accion) {
                    'INSERT' => '(dato de salud registrado)', 'DELETE' => '(dato de salud eliminado)', default => '(dato de salud modificado)'}],
                $accion === 'INSERT' => ['', $valor],
                $accion === 'DELETE' => [$valor, ''],
                default => [$this->hasCast($campo, ['date', 'immutable_date']) && $this->getRawOriginal($campo) !== null
                    ? substr((string) $this->getRawOriginal($campo), 0, 10) : $this->getRawOriginal($campo), $valor],
            };
        }

        return $campos;
    }
}
