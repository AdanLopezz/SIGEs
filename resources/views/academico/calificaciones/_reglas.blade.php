{{-- Reglas del cálculo de la definitiva (config/siges.php y calificación mínima del plantel). --}}
<dl class="dato-lista mb-0">
    <dt>Definitiva</dt><dd>Σ(calificación × peso) / 100</dd>
    <dt>Decimales</dt><dd>{{ $reglas['decimales'] }}</dd>
    <dt>Ajuste</dt><dd>{{ $reglas['redondear'] ? 'Se redondea (5.95 → 6.0)' : 'Se trunca (5.95 → 5.9)' }}</dd>
    <dt>Aprueba con</dt><dd>{{ $reglas['minima'] }} o más</dd>
    <dt>Reprobatoria</dt><dd>{{ $reglas['asentada'] === null ? 'Se asienta el valor real' : 'Se asienta '.$reglas['asentada'] }}</dd>
</dl>
