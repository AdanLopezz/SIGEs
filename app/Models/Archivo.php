<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;

// Los archivos son evidencia: no se editan ni se borran. Reemplazar = subir uno nuevo y apuntar al nuevo id.
class Archivo extends Model
{
    use Auditable;

    // Los archivos no se editan ni se borran: basta con registrar su alta.
    protected array $auditarAcciones = ['INSERT'];

    public const UPDATED_AT = null;

    public const DISCO = 'local';

    public const MIMES = ['pdf', 'jpg', 'jpeg', 'png'];

    public const MAXIMO_KB = 10240;

    protected $table = 'archivo';

    protected $fillable = ['disco', 'ruta', 'nombre_original', 'tipo_mime', 'tamano_bytes', 'sha256', 'subido_por_user_id'];

    protected function casts(): array
    {
        return ['tamano_bytes' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los archivos no se modifican; sube uno nuevo.'));
        static::deleting(fn () => throw new LogicException('Los archivos no se borran; se conservan como evidencia.'));
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por_user_id');
    }

    public static function guardar(UploadedFile $archivo, string $carpeta, User $usuario): self
    {
        $carpeta = trim($carpeta, '/');
        if (! preg_match('/^[a-z0-9_-]+(\/[a-z0-9_-]+)*$/', $carpeta)) {
            throw new InvalidArgumentException("La carpeta «{$carpeta}» no es válida.");
        }
        $extension = mb_strtolower($archivo->guessExtension() ?: $archivo->getClientOriginalExtension());
        $directorio = $carpeta.'/'.now()->format('Y/m');
        $nombre = Str::uuid().($extension !== '' ? '.'.$extension : '');
        $disco = Storage::disk(self::DISCO);

        $ruta = $disco->putFileAs($directorio, $archivo, $nombre);
        if ($ruta === false) {
            throw new RuntimeException('No se pudo guardar el archivo en el disco.');
        }
        $borrar = fn () => $disco->delete($ruta);

        try {
            $sha256 = hash_file('sha256', $disco->path($ruta)) ?: throw new RuntimeException('No se pudo calcular el sha256 del archivo.');
            // Savepoint propio: si la inserción falla no deja abortada la transacción del módulo.
            $registro = DB::transaction(fn () => static::create([
                'disco' => self::DISCO,
                'ruta' => $ruta,
                'nombre_original' => Str::limit($archivo->getClientOriginalName(), 250, ''),
                'tipo_mime' => $archivo->getMimeType() ?: 'application/octet-stream',
                'tamano_bytes' => $archivo->getSize(),
                'sha256' => $sha256,
                'subido_por_user_id' => $usuario->id,
            ]));
        } catch (Throwable $excepcion) {
            $borrar();
            throw $excepcion;
        }

        static::borrarSiSeRevierte($borrar);

        return $registro;
    }

    // Si cualquier transacción abierta se revierte, el archivo no debe quedar huérfano.
    // DB::afterRollBack() solo registra en la transacción más interna, y Laravel descarta ese
    // callback cuando ella confirma y después se revierte una exterior; por eso se registra en
    // todos los niveles abiertos (borrar dos veces no tiene efecto).
    private static function borrarSiSeRevierte(callable $borrar): void
    {
        $conexion = DB::connection()->getName();
        app('db.transactions')->callbackApplicableTransactions()
            ->filter(fn ($transaccion) => $transaccion->connection === $conexion)
            ->each(fn ($transaccion) => $transaccion->addCallbackForRollback($borrar));
    }

    public function existeFisicamente(): bool
    {
        return Storage::disk($this->disco)->exists($this->ruta);
    }

    public function verificarIntegridad(): bool
    {
        return $this->existeFisicamente()
            && hash_equals(trim($this->sha256), (string) hash_file('sha256', Storage::disk($this->disco)->path($this->ruta)));
    }

    public function getEsPdfAttribute(): bool
    {
        return $this->tipo_mime === 'application/pdf';
    }

    public function getEsImagenAttribute(): bool
    {
        return str_starts_with((string) $this->tipo_mime, 'image/');
    }

    public function getTamanoLegibleAttribute(): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $tamano = (float) $this->tamano_bytes;
        $indice = 0;
        while ($tamano >= 1024 && $indice < count($unidades) - 1) {
            $tamano /= 1024;
            $indice++;
        }

        return ($indice === 0 ? (string) (int) $tamano : number_format($tamano, 1, '.', '')).' '.$unidades[$indice];
    }

    // Reglas para los Form Requests: 'expediente' => Archivo::reglas().
    public static function reglas(bool $requerido = true): array
    {
        return ['bail', $requerido ? 'required' : 'nullable', 'file', 'mimes:'.implode(',', self::MIMES), 'max:'.self::MAXIMO_KB];
    }

    // Mensajes para unir en messages(): parent::messages() + Archivo::mensajes('expediente').
    public static function mensajes(string $campo): array
    {
        return [
            "{$campo}.required" => 'Selecciona un archivo.',
            "{$campo}.file" => 'El archivo no se recibió correctamente. Vuelve a seleccionarlo.',
            "{$campo}.uploaded" => 'No se pudo subir el archivo. Revisa que no supere 10 MB.',
            "{$campo}.mimes" => 'El archivo debe ser PDF, JPG o PNG.',
            "{$campo}.max" => 'El archivo no debe superar 10 MB.',
        ];
    }
}
