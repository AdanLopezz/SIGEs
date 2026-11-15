<?php

namespace App\Http\Controllers\Archivos;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArchivoController extends Controller
{
    public function ver(Archivo $archivo)
    {
        if (! $archivo->existeFisicamente()) {
            return $this->error($archivo, 404, 'El archivo no se encuentra en el servidor.', 'Archivo físico no encontrado');
        }
        if (! $archivo->verificarIntegridad()) {
            return $this->error($archivo, 409, 'El archivo no supera la verificación de integridad y no se puede mostrar.', 'El sha256 del archivo no coincide');
        }

        $cabeceras = ['Content-Type' => $archivo->tipo_mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        $disco = Storage::disk($archivo->disco);

        return $archivo->es_pdf || $archivo->es_imagen
            ? $disco->response($archivo->ruta, $archivo->nombre_original, $cabeceras, 'inline')
            : $disco->download($archivo->ruta, $archivo->nombre_original, $cabeceras);
    }

    private function error(Archivo $archivo, int $estado, string $mensaje, string $registro)
    {
        Log::error($registro, ['archivo_id' => $archivo->id, 'disco' => $archivo->disco, 'ruta' => $archivo->ruta, 'user_id' => auth()->id()]);

        return response()->view('archivos.error', compact('archivo', 'mensaje'), $estado);
    }
}
