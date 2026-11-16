@extends('layouts.app')
@php
    $persona = $alumno->persona;
    $actual = $alumno->inscripcionActual;
    $usuario = $persona->user;
    $puedeEditar = auth()->user()->can('editar', $alumno);
    $activo = $alumno->estaActivo();
    $credencial = $alumno->credencialActiva;
@endphp
@section('titulo', $persona->nombre_completo)
@section('contenido')
    <x-page-header :titulo="$persona->nombre_completo" :volver="[route('alumnos.index'), 'Volver a Alumnos']">
        <x-slot:subtitulo><span class="mono">{{ $alumno->matricula }}</span> · {{ $alumno->carrera->nombre }} · <x-badge-estado :estado="$alumno->estado->nombre" /></x-slot:subtitulo>
        <x-slot:acciones>
            @can('egreso.administrar')
                <a href="{{ route('egreso.show', $alumno) }}" class="btn btn-outline-siges"><i class="bi bi-mortarboard" aria-hidden="true"></i> Egreso y procesos</a>
            @endcan
            {{-- Inscribir, cambiar de grupo, baja y reingreso operan sobre el periodo activo: no se ofrecen al consultar otro. --}}
            @if ($activo && $operaPeriodoActivo)
                @can('inscripciones.administrar')
                    <a href="{{ route('alumnos.inscripcion', $alumno) }}" class="btn btn-outline-siges"><i class="bi bi-journal-check" aria-hidden="true"></i> {{ $actual ? 'Cambiar de grupo' : 'Inscribir' }}</a>
                @endcan
            @endif
            @if ($operaPeriodoActivo)
            @can('bajas.registrar')
                @if ($activo && $actual)
                    <button type="button" class="btn btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalBaja"><i class="bi bi-person-dash" aria-hidden="true"></i> Registrar baja</button>
                @elseif ($alumno->estado->nombre === \App\Models\CatEstadoAlumno::BAJA_TEMPORAL)
                    <a href="{{ route('alumnos.reingreso', $alumno) }}" class="btn btn-outline-siges"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Reingresar</a>
                @endif
            @endcan
            @endif
            @if ($puedeEditar)<a href="{{ route('alumnos.edit', $alumno) }}" class="btn btn-siges"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>@endif
        </x-slot:acciones>
    </x-page-header>
    {{-- Errores del modal de baja (el modal se cierra al recargar). --}}
    @if ($errors->hasAny(['tipo', 'fecha', 'motivo']))<x-errores-formulario />@endif

    @if ($avisos['sinDatosPersonales']->isNotEmpty())
        <x-aviso tipo="aviso">
            Falta el consentimiento de datos personales de: {{ $avisos['sinDatosPersonales']->map(fn ($tutor) => $tutor->persona->nombre_completo)->implode(', ') }}.
            <a href="{{ route('alumnos.consentimientos.index', $alumno) }}">Ver consentimientos</a>.
        </x-aviso>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <x-tarjeta titulo="Datos personales" icono="bi-person-vcard" class="h-100">
                <dl class="dato-lista">
                    <dt>CURP</dt><dd class="mono">{{ $persona->curp }}</dd>
                    <dt>Correo personal</dt><dd>{{ $persona->email_personal }}</dd>
                    <dt>Teléfono</dt><dd>{{ $persona->telefono }}</dd>
                    <dt>Domicilio</dt><dd>{{ $persona->calle_numero }}, {{ $persona->colonia }}, {{ $persona->municipio }}, C.P. {{ $persona->codigo_postal }}</dd>
                </dl>
            </x-tarjeta>
        </div>
        <div class="col-lg-6">
            <x-tarjeta titulo="Datos escolares" icono="bi-mortarboard" class="h-100">
                <dl class="dato-lista">
                    {{-- Inscripción y grupo del periodo de consulta (selector de la barra superior). --}}
                    <dt>Grupo <small class="mono texto-tenue">{{ $periodoConsulta?->clave }}</small></dt>
                    <dd>
                        @if ($inscripcionConsulta)
                            {{ $inscripcionConsulta->grupo->nombre_completo }} · {{ $inscripcionConsulta->grupo->semestre }}.º semestre
                            @unless ($inscripcionConsulta->estado === \App\Models\AcaInscripcion::INSCRITO)<x-badge-estado :estado="$inscripcionConsulta->estado" />@endunless
                        @else
                            Sin inscripción en {{ $periodoConsulta?->nombre_visible ?? 'el periodo' }}
                        @endif
                    </dd>
                    <dt>Generación</dt><dd>{{ $alumno->generacion }}</dd>
                    <dt>Fecha de ingreso</dt><dd>{{ $alumno->fecha_ingreso->format('d/m/Y') }}</dd>
                    <dt>Correo institucional</dt><dd>{{ $alumno->email_institucional }}</dd>
                    <dt>Estado</dt><dd><x-badge-estado :estado="$alumno->estado->nombre" /></dd>
                </dl>
            </x-tarjeta>
        </div>

        @can('verSalud', $alumno)
            <div class="col-lg-6">
                <x-tarjeta titulo="Salud" icono="bi-heart-pulse" class="h-100">
                    @if ($avisos['sinDatosSalud'])
                        <x-aviso tipo="aviso">Ningún tutor tiene vigente el consentimiento de datos de salud.</x-aviso>
                    @endif
                    @if ($alumno->salud)
                        <dl class="dato-lista">
                            <dt>NSS</dt><dd class="mono">{{ $alumno->salud->nss }}</dd>
                            <dt>Tipo de sangre</dt><dd>{{ $alumno->salud->tipo_sangre }}</dd>
                            <dt>Alergias</dt><dd>{{ $alumno->salud->alergias }}</dd>
                            <dt>Condiciones médicas</dt><dd>{{ $alumno->salud->condiciones_medicas }}</dd>
                        </dl>
                    @else
                        <x-estado-vacio icono="bi-heart-pulse" texto="Sin datos de salud registrados." />
                    @endif
                </x-tarjeta>
            </div>
        @endcan

        <div class="col-lg-6">
            <x-tarjeta titulo="Expediente" icono="bi-folder2-open" class="h-100">
                <p class="mb-2"><x-archivo-enlace :archivo="$alumno->expediente" /></p>
                <small class="texto-tenue">Acta de nacimiento, CURP y certificado de secundaria. Subido el {{ $alumno->expediente->created_at->format('d/m/Y') }}.</small>
            </x-tarjeta>
        </div>

        <div class="col-lg-6">
            <x-tarjeta titulo="Consentimientos" icono="bi-file-earmark-check" class="h-100">
                <x-slot:acciones><a href="{{ route('alumnos.consentimientos.index', $alumno) }}" class="btn btn-sm btn-outline-siges">{{ $puedeEditar ? 'Registrar y ver' : 'Ver' }}</a></x-slot:acciones>
                @foreach ($alumno->tutores->where('activo', true) as $tutor)
                    <p class="mb-1"><strong>{{ $tutor->persona->nombre_completo }}</strong> <small class="texto-tenue">· {{ $tutor->parentesco_nombre }}</small></p>
                    <ul class="list-unstyled small mb-3">
                        @foreach ($tiposConsentimiento as $tipo => $nombre)
                            @php $vigente = $consentimientos[$tutor->id][$tipo] ?? null; @endphp
                            <li class="mb-1">{{ $nombre }}: <x-badge-estado :estado="$vigente ? ($vigente->otorgado ? 'OTORGADO' : 'REVOCADO') : 'SIN_CONSENTIMIENTO'" /></li>
                        @endforeach
                    </ul>
                @endforeach
            </x-tarjeta>
        </div>

        <div class="col-lg-6">
            <x-tarjeta titulo="Credencial" icono="bi-qr-code" class="h-100">
                <p class="mb-2">
                    @if ($credencial)<x-badge-estado estado="CREDENCIAL_ACTIVA" /> desde el {{ $credencial->fecha_emision->format('d/m/Y') }}@else<x-badge-estado estado="SIN_CREDENCIAL" />@endif
                </p>
                <p class="texto-tenue small">El código QR no se guarda: para volver a imprimirla hay que reponerla, y la anterior deja de funcionar.</p>
                @if ($puedeEditar)
                    @if (! $activo)
                        <p class="texto-tenue small mb-0">Solo se emite credencial a alumnos con estado Activo.</p>
                    @elseif ($credencial)
                        <button type="button" class="btn btn-sm btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalReponer" data-accion="{{ route('alumnos.credencial.emitir', $alumno) }}" data-nombre="{{ $persona->nombre_completo }}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Reponer</button>
                    @else
                        <form method="POST" action="{{ route('alumnos.credencial.emitir', $alumno) }}">@csrf<button type="submit" class="btn btn-sm btn-siges"><i class="bi bi-printer" aria-hidden="true"></i> Emitir e imprimir</button></form>
                    @endif
                @endif
            </x-tarjeta>
        </div>

        <div class="col-lg-6">
            <x-tarjeta titulo="Cuenta de acceso" icono="bi-key" class="h-100">
                @if ($usuario)
                    <p class="mb-2"><span class="mono">{{ $usuario->email }}</span> <x-badge-estado :estado="$usuario->roles->isNotEmpty() ? 'CON_ACCESO' : 'SIN_ACCESO'" /></p>
                    <p>@foreach ($usuario->roles as $rol)<span class="chip">{{ $rol->name }}</span>@endforeach</p>
                @else
                    <p class="texto-tenue">El alumno aún no tiene cuenta. Se creará con el usuario <span class="mono">{{ $alumno->email_institucional }}</span> (su correo institucional), el rol Alumno y una contraseña temporal que deberá cambiar al entrar.</p>
                @endif
                @if ($puedeEditar)@include('control-escolar.alumnos._cuenta', ['usuario' => $usuario, 'rol' => 'Alumno', 'accion' => route('alumnos.crear-cuenta', $alumno), 'datos' => route('alumnos.cuenta', $alumno)])@endif
            </x-tarjeta>
        </div>

        @if ($incidencias !== null)
            <div class="col-lg-6">
                <x-tarjeta titulo="Incidencias" icono="bi-exclamation-triangle" class="h-100">
                    <x-slot:acciones><a href="{{ route('incidencias.index', ['alumno' => $alumno->id]) }}" class="btn btn-sm btn-outline-siges">Ver todas</a></x-slot:acciones>
                    @forelse ($incidencias as $incidencia)
                        <div class="d-flex align-items-start justify-content-between gap-2 py-2 @unless ($loop->first) border-top @endunless">
                            <div>
                                <a href="{{ route('incidencias.show', $incidencia) }}"><strong>{{ $incidencia->tipo->nombre }}</strong></a>
                                <small class="d-block texto-tenue">{{ $incidencia->fecha_inicio->format('d/m/Y') }}@if ($incidencia->fecha_fin) al {{ $incidencia->fecha_fin->format('d/m/Y') }}@endif</small>
                            </div>
                            <div class="d-flex flex-wrap justify-content-end gap-1"><x-badge-estado :estado="$incidencia->tipo->nivel_gravedad" /><x-badge-estado :estado="$incidencia->estado" /></div>
                        </div>
                    @empty
                        <x-estado-vacio icono="bi-emoji-smile" texto="Sin incidencias registradas." />
                    @endforelse
                </x-tarjeta>
            </div>
        @endif

        @if ($seguimientos !== null)
            {{-- Confidencial: solo con seguimiento.administrar y los casos visibles (de todos los periodos). Nunca en el portal. --}}
            <div class="col-lg-6">
                <x-tarjeta titulo="Seguimiento" icono="bi-person-heart" class="h-100">
                    <x-slot:acciones><a href="{{ route('seguimiento.index', ['alumno' => $alumno->id]) }}" class="btn btn-sm btn-outline-siges">Ver en Seguimiento</a></x-slot:acciones>
                    @forelse ($seguimientos as $caso)
                        <div class="d-flex align-items-start justify-content-between gap-2 py-2 @unless ($loop->first) border-top @endunless">
                            <div>
                                <a href="{{ route('seguimiento.show', $caso) }}"><strong>#{{ $caso->id }} · {{ $caso->motivo_nombre }}</strong></a>
                                <small class="d-block texto-tenue">{{ $caso->fecha_apertura->format('d/m/Y') }} · {{ $caso->inscripcion->grupo->periodo->nombre_visible }}</small>
                                <small class="d-block texto-tenue">Última nota: {{ $caso->ultimaNota ? $caso->ultimaNota->fecha->format('d/m/Y').' · '.$caso->ultimaNota->tipo_nombre : 'sin notas' }}</small>
                            </div>
                            <div class="d-flex flex-wrap justify-content-end gap-1">@if ($caso->riesgo_abandono)<x-badge-estado estado="RIESGO_ABANDONO" />@endif<x-badge-estado :estado="$caso->estado" /></div>
                        </div>
                    @empty
                        <x-estado-vacio icono="bi-person-heart" texto="Sin casos de seguimiento." />
                    @endforelse
                </x-tarjeta>
            </div>
        @endif

        @if ($boleta || $recursamientos->isNotEmpty())
            @php $inscripcionBoleta = $boleta['inscripcion'] ?? null; @endphp
            <div class="col-12">
                {{-- Solo se muestran calificaciones de evaluaciones cerradas; las abiertas aparecen "En captura". --}}
                <x-tarjeta titulo="Calificaciones" icono="bi-clipboard-data" sin-relleno>
                    <x-slot:acciones>@if ($inscripcionBoleta)<span class="texto-tenue">{{ $inscripcionBoleta->grupo->nombre_completo }}@if ($inscripcionBoleta->estado !== \App\Models\AcaInscripcion::INSCRITO) · <x-badge-estado :estado="$inscripcionBoleta->estado" />@endif</span>@endif</x-slot:acciones>
                    @if ($adeudos['total'] > $adeudos['maximo'])
                        <div class="px-4 pt-3"><x-aviso tipo="peligro">Adeuda {{ $adeudos['total'] }} materias reprobadas sin regularizar; el máximo para reinscribirse es {{ $adeudos['maximo'] }}.</x-aviso></div>
                    @endif
                    @if (! $boleta)
                        <x-estado-vacio icono="bi-clipboard-data" :texto="'Sin inscripción en '.($periodoConsulta?->nombre_visible ?? 'el periodo').'.'" />
                    @elseif ($boleta['materias']->isEmpty())
                        <x-estado-vacio icono="bi-clipboard-data" texto="El grupo no tiene materias asignadas." />
                    @else
                        <div class="table-responsive">
                            <table class="table tabla-siges align-middle mb-0">
                                <thead><tr><th scope="col">Materia</th>@foreach ($boleta['tipos'] as $tipo)<th scope="col" class="text-center">{{ $tipo->nombre }}</th>@endforeach<th scope="col" class="text-center">Definitiva</th><th scope="col">Oportunidad</th><th scope="col">Estado</th></tr></thead>
                                <tbody>
                                @foreach ($boleta['materias'] as $materiaBoleta)
                                    @php $resultadoBoleta = $boleta['resultados']->get($materiaBoleta->id); @endphp
                                    <tr>
                                        <td><strong>{{ $materiaBoleta->asignatura->nombre }}</strong><small class="d-block mono">{{ $materiaBoleta->asignatura->clave }}</small></td>
                                        @foreach ($boleta['tipos'] as $tipo)
                                            @php $evaluacionBoleta = $materiaBoleta->evaluaciones->firstWhere('cat_tipo_evaluacion_id', $tipo->id); @endphp
                                            <td class="text-center">
                                                @if (! $evaluacionBoleta)
                                                    <span class="texto-tenue">—</span>
                                                @elseif (! $evaluacionBoleta->cerrada)
                                                    <x-badge-estado estado="EN_CAPTURA" />
                                                @else
                                                    <span class="mono">{{ $boleta['calificaciones']->get($evaluacionBoleta->id)?->valor ?? '—' }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center mono">{{ $resultadoBoleta?->calificacion ?? '—' }}</td>
                                        <td>@if ($resultadoBoleta)<x-badge-estado :estado="$resultadoBoleta->oportunidad" />@else<span class="texto-tenue">—</span>@endif</td>
                                        <td><x-badge-estado :estado="$resultadoBoleta?->estado ?? 'SIN_RESULTADO'" /></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @if ($recursamientos->isNotEmpty())
                        <h3 class="panel-card-titulo px-4 pt-3"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Recursamientos del periodo</h3>
                        <div class="table-responsive">
                            <table class="table tabla-siges align-middle mb-0">
                                <thead><tr><th scope="col">Materia</th><th scope="col">Grupo</th><th scope="col">Docente</th><th scope="col">Reprobó en</th><th scope="col">Inicio</th><th scope="col">Resultado</th></tr></thead>
                                <tbody>
                                @foreach ($recursamientos as $recursamiento)
                                    @php $resultadoRecursamiento = $recursamiento->resultadoDestino(); @endphp
                                    <tr>
                                        <td><strong>{{ $recursamiento->materia->asignatura->nombre }}</strong><small class="d-block mono">{{ $recursamiento->materia->asignatura->clave }}</small></td>
                                        <td>{{ $recursamiento->materia->grupo->identificador }}</td>
                                        <td>{{ $recursamiento->materia->docente->nombre }}</td>
                                        <td>{{ $recursamiento->origen->materia->grupo->identificador }} · {{ $recursamiento->origen->materia->grupo->periodo->clave }}</td>
                                        <td>{{ $recursamiento->fecha_inicio->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($resultadoRecursamiento)
                                                <x-badge-estado :estado="$resultadoRecursamiento->estado" :texto="\App\View\Components\BadgeEstado::ESTADOS[$resultadoRecursamiento->estado][1].' · '.$resultadoRecursamiento->calificacion" />
                                            @else
                                                <x-badge-estado estado="EN_RECURSAMIENTO" />
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <div class="tabla-pie">
                        <span>@if ($boleta){{ $boleta['materias']->count() }} {{ $boleta['materias']->count() === 1 ? 'materia' : 'materias' }} · periodo {{ $inscripcionBoleta->grupo->periodo?->nombre_visible }} ({{ $inscripcionBoleta->grupo->periodo?->clave }})@endif @if ($recursamientos->isNotEmpty()) · {{ $recursamientos->count() }} {{ $recursamientos->count() === 1 ? 'recursamiento' : 'recursamientos' }}@endif</span>
                        <span>Aprobatoria: {{ \App\Models\TrxResultadoAsignatura::calificacionMinima() }}</span>
                    </div>
                </x-tarjeta>
            </div>
        @endif

        @if ($asistenciaClase->isNotEmpty())
            <div class="col-12">
                {{-- Pase de lista por clase del periodo de consulta. Porcentaje = (A + R) / (clases − J); bajo el mínimo solo se avisa. --}}
                <x-tarjeta titulo="Asistencia por materia" icono="bi-person-check" sin-relleno>
                    <div class="table-responsive">
                        <table class="table tabla-siges align-middle mb-0">
                            <thead><tr><th scope="col">Materia</th><th scope="col">Clases</th><th scope="col">Faltas</th><th scope="col">Retardos</th><th scope="col">Justificadas</th><th scope="col">Porcentaje</th></tr></thead>
                            <tbody>
                            @foreach ($asistenciaClase as $fila)
                                <tr @class(['bajo-minimo' => $fila->bajo_minimo])>
                                    <td><strong>{{ $fila->materia->asignatura->nombre }}</strong>@if ($fila->recursamiento) <x-badge-estado estado="RECURSAMIENTO" />@endif<small class="d-block texto-tenue">{{ $fila->materia->docente?->nombre }}</small></td>
                                    <td>{{ $fila->clases }}</td>
                                    <td>{{ $fila->faltas }}</td>
                                    <td>{{ $fila->retardos }}</td>
                                    <td>{{ $fila->justificadas }}</td>
                                    <td>@if ($fila->clases === 0)<span class="texto-tenue">Sin registros</span>@else<strong>{{ \App\Models\TrxAsistenciaClase::formatear($fila->porcentaje) }}</strong>@if ($fila->bajo_minimo) <x-badge-estado estado="BAJO_MINIMO" />@endif @endif</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="tabla-pie"><span>Las faltas justificadas no cuentan en el porcentaje.</span><span>Mínimo {{ \App\Models\TrxAsistenciaClase::formatear(\App\Models\TrxAsistenciaClase::minimoPorcentaje()) }} (solo aviso)</span></div>
                </x-tarjeta>
            </div>
        @endif

        <div class="col-12">
            <x-tarjeta titulo="Tutores" icono="bi-people" sin-relleno>
                <x-slot:acciones>@if ($puedeEditar)<a href="{{ route('alumnos.tutores.index', $alumno) }}" class="btn btn-sm btn-outline-siges">Administrar tutores</a>@endif</x-slot:acciones>
                <div class="table-responsive">
                    <table class="table tabla-siges align-middle mb-0">
                        <thead><tr><th scope="col">Nombre</th><th scope="col">Parentesco</th><th scope="col">Contacto</th><th scope="col">Emergencia</th><th scope="col">Notificaciones</th><th scope="col">Cuenta</th>@if ($puedeEditar)<th scope="col" class="text-end">Acciones</th>@endif</tr></thead>
                        <tbody>
                        @foreach ($alumno->tutores as $tutor)
                            <tr>
                                <td><strong>{{ $tutor->persona->nombre_completo }}</strong><small class="d-block mono">{{ $tutor->persona->curp }}</small>@unless ($tutor->activo)<x-badge-estado estado="TUTOR_INACTIVO" />@endunless</td>
                                <td>{{ $tutor->parentesco_nombre }}</td>
                                <td>{{ $tutor->persona->telefono }}<small class="d-block texto-tenue">{{ $tutor->persona->email_personal }}</small></td>
                                <td>{{ $tutor->es_contacto_emergencia ? 'Sí' : 'No' }}</td>
                                <td>{{ $tutor->recibe_notificaciones ? 'Sí' : 'No' }}</td>
                                <td class="mono">{{ $tutor->persona->user?->email ?? '—' }}</td>
                                @if ($puedeEditar)
                                    <td><div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-siges" href="{{ route('alumnos.tutores.edit', [$alumno, $tutor]) }}" aria-label="Editar a {{ $tutor->persona->nombre_completo }}">Editar</a>
                                        <x-menu-acciones :etiqueta="$tutor->persona->nombre_completo">
                                            @if ($tutor->activo)<li>@include('control-escolar.alumnos._cuenta', ['usuario' => $tutor->persona->user, 'rol' => 'Padre de familia', 'accion' => route('alumnos.tutores.crear-cuenta', [$alumno, $tutor]), 'datos' => route('alumnos.tutores.cuenta', [$alumno, $tutor]), 'enMenu' => true,
                                                'modal' => isset($usuariosSugeridos[$tutor->id]) ? ['usuario' => $usuariosSugeridos[$tutor->id], 'nombre' => $tutor->persona->nombre, 'apellido' => $tutor->persona->primer_apellido] : null])</li>@endif
                                        </x-menu-acciones>
                                    </div></td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="tabla-pie"><span>{{ $alumno->tutores->count() }} {{ $alumno->tutores->count() === 1 ? 'tutor' : 'tutores' }}</span></div>
            </x-tarjeta>
        </div>

        <div class="col-12">
            <x-tarjeta titulo="Historial de inscripciones" icono="bi-clock-history" sin-relleno>
                @if ($alumno->inscripciones->isEmpty())
                    <x-estado-vacio icono="bi-journal" texto="Sin inscripciones registradas." />
                @else
                    <div class="table-responsive">
                        <table class="table tabla-siges align-middle mb-0">
                            <thead><tr><th scope="col">Periodo</th><th scope="col">Grupo</th><th scope="col">Semestre</th><th scope="col">Fecha</th><th scope="col">Estado</th><th scope="col">Baja</th></tr></thead>
                            <tbody>
                            @foreach ($alumno->inscripciones as $inscripcion)
                                <tr>
                                    <td>{{ $inscripcion->grupo->periodo->clave }}</td>
                                    <td>{{ $inscripcion->grupo->nombre_completo }}</td>
                                    <td>{{ $inscripcion->grupo->semestre }}</td>
                                    <td>{{ $inscripcion->fecha_inscripcion->format('d/m/Y') }}</td>
                                    <td><x-badge-estado :estado="$inscripcion->estado" /></td>
                                    <td>@if ($inscripcion->baja)<x-badge-estado :estado="$inscripcion->baja->tipo" /> el {{ $inscripcion->baja->fecha->format('d/m/Y') }}<small class="d-block texto-tenue">{{ $inscripcion->baja->motivo }}</small>@else<span class="texto-tenue">—</span>@endif</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="tabla-pie"><span>{{ $alumno->inscripciones->count() }} {{ $alumno->inscripciones->count() === 1 ? 'inscripción' : 'inscripciones' }}</span></div>
            </x-tarjeta>
        </div>

        <div class="col-12">
            <x-historial tabla="per_alumno" :registro="$alumno->id" :relacionados="$historial" />
        </div>
    </div>
@endsection

@push('modales')
    @if ($usuariosSugeridos)
        {{-- Crear cuenta de un tutor: el usuario y el nombre llegan del botón (usuario-generado.js). --}}
        <x-modal id="modalCuentaTutor" titulo="Crear cuenta" icono="bi-person-plus" boton="Crear cuenta" confirmar>
            <p>Se creará la cuenta de <strong data-campo="nombre"></strong> con el rol Padre de familia y una contraseña temporal que deberá cambiar al entrar.</p>
            <div class="row g-3"><x-campo-usuario id="usuarioTutor" col="col-12" ayuda="Lo genera el sistema con el nombre y el primer apellido del tutor." /></div>
        </x-modal>
    @endif
    @can('bajas.registrar')
        @if ($activo && $actual && $operaPeriodoActivo)
            @php [$fechaMinima, $fechaMaxima] = $limitesBaja; @endphp
            <x-modal id="modalBaja" titulo="Registrar baja" icono="bi-person-dash" :accion="route('alumnos.bajas.store', $alumno)" boton="Registrar baja" peligro>
                <p>{{ $persona->nombre_completo }} saldrá de {{ $actual->grupo->nombre_completo }} y sus credenciales se desactivarán. La baja definitiva también le quita el acceso de alumno.</p>
                <div class="row g-3">
                    <x-seleccion name="tipo" label="Tipo" col="col-md-6" :opciones="\App\Models\AcaBaja::TIPOS" :requerido="true" />
                    <x-campo name="fecha" label="Fecha" type="date" col="col-md-6" :value="$fechaMaxima->format('Y-m-d')" :min="$fechaMinima->format('Y-m-d')" :max="$fechaMaxima->format('Y-m-d')" :requerido="true" />
                    <div class="col-12">
                        <label for="motivo" class="form-label">Motivo <span class="requerido" aria-hidden="true">*</span></label>
                        <textarea id="motivo" name="motivo" rows="3" class="form-control @error('motivo') is-invalid @enderror" minlength="10" maxlength="1000" required aria-describedby="motivoAyuda">{{ old('motivo') }}</textarea>
                        <div class="form-text" id="motivoAyuda">Al menos 10 caracteres. Queda en la baja y en la bitácora.</div>
                    </div>
                </div>
            </x-modal>
        @endif
    @endcan
    @if ($puedeEditar && $activo && $credencial)
        <x-modal id="modalReponer" titulo="Reponer credencial" icono="bi-arrow-repeat" boton="Reponer e imprimir" peligro confirmar>
            Se emitirá una credencial nueva para <strong data-campo="nombre"></strong>. <strong>La credencial anterior dejará de funcionar.</strong>
        </x-modal>
    @endif
@endpush
