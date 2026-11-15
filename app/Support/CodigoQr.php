<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

// Código QR en SVG (bacon/bacon-qr-code), listo para insertarse en una vista.
class CodigoQr
{
    public static function svg(string $texto, int $tamano = 200): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($tamano, 1), new SvgImageBackEnd)))->writeString($texto);

        // Sin la declaración XML, para poder insertarlo dentro del HTML.
        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }
}
