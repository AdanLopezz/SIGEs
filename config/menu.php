<?php

/*
|--------------------------------------------------------------------------
| Menú lateral de SIGEs
|--------------------------------------------------------------------------
| Cada opción indica:
|   titulo  → texto que se muestra
|   ruta    → nombre de la ruta (si la ruta aún no existe, la opción no aparece)
|   icono   → clase de Bootstrap Icons (https://icons.getbootstrap.com)
|   permiso → permiso de Spatie necesario (null = cualquier usuario con sesión;
|             una lista = basta con tener uno de ellos)
|   rol     → (opcional) rol necesario, o una lista (basta con uno). Si hay permiso y rol, se piden los dos.
|             Lo usa el portal de Alumno y Padre de familia, que no tiene permisos.
|
| Una sección solo aparece si el usuario puede ver al menos una de sus opciones.
| Una ruta repetida en dos secciones se muestra una sola vez (la primera).
*/

return [

    [
        'seccion' => null,
        'opciones' => [
            ['titulo' => 'Inicio', 'ruta' => 'dashboard', 'icono' => 'bi-house-door', 'permiso' => null],
        ],
    ],

    [
        'seccion' => 'Mi escuela',
        'opciones' => [
            ['titulo' => 'Calificaciones',     'ruta' => 'mi-escuela.calificaciones.index', 'icono' => 'bi-clipboard-data', 'permiso' => null, 'rol' => 'Alumno'],
            ['titulo' => 'Horario',            'ruta' => 'mi-escuela.horario.index',        'icono' => 'bi-calendar-week', 'permiso' => null, 'rol' => 'Alumno'],
            ['titulo' => 'Asistencia',         'ruta' => 'mi-escuela.asistencia.index',     'icono' => 'bi-calendar-check', 'permiso' => null, 'rol' => 'Alumno'],
            ['titulo' => 'Incidencias',        'ruta' => 'mi-escuela.incidencias.index',    'icono' => 'bi-exclamation-triangle', 'permiso' => null, 'rol' => 'Alumno'],
            ['titulo' => 'Permisos de salida', 'ruta' => 'mi-escuela.permisos.index',       'icono' => 'bi-door-open', 'permiso' => null, 'rol' => 'Alumno'],
            ['titulo' => 'Credencial',         'ruta' => 'mi-escuela.credencial.index',     'icono' => 'bi-qr-code', 'permiso' => null, 'rol' => 'Alumno'],
            ['titulo' => 'Mis datos',          'ruta' => 'mi-escuela.datos.index',          'icono' => 'bi-person-vcard', 'permiso' => null, 'rol' => 'Alumno'],
            ['titulo' => 'Avisos',             'ruta' => 'avisos.index',                    'icono' => 'bi-bell', 'permiso' => null, 'rol' => 'Alumno'],
        ],
    ],

    [
        'seccion' => 'Mis hijos',
        'opciones' => [
            ['titulo' => 'Mis hijos',          'ruta' => 'mis-hijos.seleccion.index',      'icono' => 'bi-people', 'permiso' => null, 'rol' => 'Padre de familia'],
            ['titulo' => 'Calificaciones',     'ruta' => 'mis-hijos.calificaciones.index', 'icono' => 'bi-clipboard-data', 'permiso' => null, 'rol' => 'Padre de familia'],
            ['titulo' => 'Horario',            'ruta' => 'mis-hijos.horario.index',        'icono' => 'bi-calendar-week', 'permiso' => null, 'rol' => 'Padre de familia'],
            ['titulo' => 'Asistencia',         'ruta' => 'mis-hijos.asistencia.index',     'icono' => 'bi-calendar-check', 'permiso' => null, 'rol' => 'Padre de familia'],
            ['titulo' => 'Incidencias',        'ruta' => 'mis-hijos.incidencias.index',    'icono' => 'bi-exclamation-triangle', 'permiso' => null, 'rol' => 'Padre de familia'],
            ['titulo' => 'Permisos de salida', 'ruta' => 'mis-hijos.permisos.index',       'icono' => 'bi-door-open', 'permiso' => null, 'rol' => 'Padre de familia'],
            ['titulo' => 'Credencial',         'ruta' => 'mis-hijos.credencial.index',     'icono' => 'bi-qr-code', 'permiso' => null, 'rol' => 'Padre de familia'],
            ['titulo' => 'Datos del alumno',   'ruta' => 'mis-hijos.datos.index',          'icono' => 'bi-person-vcard', 'permiso' => null, 'rol' => 'Padre de familia'],
            ['titulo' => 'Avisos',             'ruta' => 'avisos.index',                   'icono' => 'bi-bell', 'permiso' => null, 'rol' => 'Padre de familia'],
        ],
    ],

    [
        'seccion' => 'Control escolar',
        'opciones' => [
            ['titulo' => 'Alumnos',       'ruta' => 'alumnos.index',       'icono' => 'bi-mortarboard',   'permiso' => 'alumnos.ver'],
            ['titulo' => 'Inscripciones', 'ruta' => 'inscripciones.index', 'icono' => 'bi-journal-check', 'permiso' => 'inscripciones.administrar'],
            ['titulo' => 'Cambio de periodo', 'ruta' => 'cambio-periodo.index', 'icono' => 'bi-calendar-range', 'permiso' => 'inscripciones.administrar'],
            ['titulo' => 'Bajas',         'ruta' => 'bajas.index',         'icono' => 'bi-person-dash',   'permiso' => 'bajas.registrar'],
            ['titulo' => 'Regularización', 'ruta' => 'regularizacion.index', 'icono' => 'bi-arrow-repeat', 'permiso' => 'recursamientos.administrar'],
            ['titulo' => 'Horarios',      'ruta' => 'horarios.index',      'icono' => 'bi-calendar-week', 'permiso' => 'horarios.administrar'],
            ['titulo' => 'Aspirantes',    'ruta' => 'aspirantes.index',    'icono' => 'bi-person-plus',   'permiso' => 'aspirantes.administrar'],
        ],
    ],

    [
        'seccion' => 'Académico',
        'opciones' => [
            ['titulo' => 'Grupos',         'ruta' => 'grupos.index',         'icono' => 'bi-people-fill',    'permiso' => 'horarios.administrar'],
            // Control Escolar consulta las evaluaciones y corrige o cierra resultados, aunque no las programe ni capture.
            ['titulo' => 'Evaluaciones',   'ruta' => 'evaluaciones.index',   'icono' => 'bi-card-checklist', 'permiso' => ['evaluaciones.programar', 'calificaciones.corregir', 'resultados.cerrar']],
            ['titulo' => 'Calificaciones', 'ruta' => 'calificaciones.index', 'icono' => 'bi-clipboard-data', 'permiso' => ['calificaciones.capturar', 'calificaciones.corregir', 'resultados.cerrar']],
            // Pase de lista por clase: el docente (asistencia_clase.registrar); Control Escolar corrige desde "Asistencia por materia".
            ['titulo' => 'Mis clases de hoy', 'ruta' => 'pase-lista.index',      'icono' => 'bi-list-check',     'permiso' => 'asistencia_clase.registrar'],
            ['titulo' => 'Mi horario',     'ruta' => 'mi-horario.index',     'icono' => 'bi-calendar-week',  'permiso' => 'asistencia_clase.registrar'],
            ['titulo' => 'Asistencia por materia', 'ruta' => 'asistencia-materia.index', 'icono' => 'bi-table', 'permiso' => ['asistencia_clase.registrar', 'asistencia_clase.corregir']],
        ],
    ],

    [
        'seccion' => 'Orientación',
        'opciones' => [
            ['titulo' => 'Control de acceso',  'ruta' => 'acceso.index',          'icono' => 'bi-qr-code-scan',         'permiso' => 'acceso.registrar'],
            // Control Escolar no tiene asistencia.ver, pero puede justificar faltas.
            ['titulo' => 'Asistencia',         'ruta' => 'asistencia.index',      'icono' => 'bi-calendar-check',       'permiso' => ['asistencia.ver', 'incidencias.cerrar', 'alumnos.editar']],
            ['titulo' => 'Asistencia por clase', 'ruta' => 'asistencia-clase.index', 'icono' => 'bi-person-check', 'permiso' => 'asistencia.ver'],
            ['titulo' => 'Incidencias',        'ruta' => 'incidencias.index',     'icono' => 'bi-exclamation-triangle', 'permiso' => 'incidencias.registrar'],
            ['titulo' => 'Permisos de salida', 'ruta' => 'permisos-salida.index', 'icono' => 'bi-door-open',            'permiso' => 'permisos_salida.administrar'],
            ['titulo' => 'Seguimiento',        'ruta' => 'seguimiento.index',     'icono' => 'bi-person-heart',         'permiso' => 'seguimiento.administrar'],
        ],
    ],

    [
        'seccion' => 'Vinculación',
        'opciones' => [
            ['titulo' => 'Formación dual', 'ruta' => 'dual.index',    'icono' => 'bi-briefcase',  'permiso' => 'dual.administrar'],
            ['titulo' => 'Egresados',      'ruta' => 'egreso.index',  'icono' => 'bi-award',      'permiso' => 'egreso.administrar'],
        ],
    ],

    [
        'seccion' => 'Planeación',
        'opciones' => [
            ['titulo' => 'Actividades', 'ruta' => 'actividades.index', 'icono' => 'bi-list-check',   'permiso' => 'actividades.administrar'],
            ['titulo' => 'Oficios',     'ruta' => 'oficios.index',     'icono' => 'bi-envelope-paper','permiso' => 'oficios.administrar'],
            ['titulo' => 'Normativos',  'ruta' => 'normativos.index',  'icono' => 'bi-book',          'permiso' => 'normativos.administrar'],
            ['titulo' => 'Encuestas',   'ruta' => 'encuestas.index',   'icono' => 'bi-ui-checks',     'permiso' => 'encuestas.administrar'],
        ],
    ],

    // Sección propia: los reportes los usan Dirección, Control Escolar, Orientación, Vinculación y Planeación.
    [
        'seccion' => 'Reportes',
        'opciones' => [
            ['titulo' => 'Reportes', 'ruta' => 'reportes.index', 'icono' => 'bi-bar-chart', 'permiso' => 'reportes.ver'],
        ],
    ],

    [
        'seccion' => 'Administración',
        'opciones' => [
            ['titulo' => 'Usuarios',      'ruta' => 'usuarios.index',     'icono' => 'bi-people',       'permiso' => 'usuarios.administrar'],
            ['titulo' => 'Configuración', 'ruta' => 'configuracion.edit', 'icono' => 'bi-gear',         'permiso' => 'configuracion.administrar'],
            ['titulo' => 'Bitácora',      'ruta' => 'auditoria.index',    'icono' => 'bi-shield-check', 'permiso' => 'auditoria.ver'],
        ],
    ],

];
