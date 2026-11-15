<?php

/*
|--------------------------------------------------------------------------
| Reglas propias de SIGEs
|--------------------------------------------------------------------------
| La calificación mínima aprobatoria está en sys_configuracion (Configuración del plantel).
*/

return [

    // Cuentas de acceso: el usuario se genera como nombre.apellido + 4 dígitos + @dominio.
    // El dominio NO es del plantel: nunca se envía correo a estas direcciones (los correos van a per_persona.email_personal).
    'cuentas' => [
        'dominio' => 'siges.com',
    ],

    // Registros por página de los listados: el usuario elige entre las opciones (sin "Todos"); se recuerda por pantalla.
    'paginacion' => [
        'opciones' => [10, 25, 50, 100],
        'por_defecto' => 25,
    ],

    'calificaciones' => [
        // Decimales de cada calificación capturada y de la definitiva (0, 1 o 2; la columna admite hasta 2).
        'decimales' => 1,

        // true  → la definitiva se redondea (5.95 queda 6.0 y aprueba con mínima 6).
        // false → la definitiva se trunca (5.95 queda 5.9 y reprueba).
        'redondear_para_aprobar' => true,

        // Calificación que se asienta cuando la definitiva es reprobatoria.
        // null → se asienta el valor real (p. ej. 5.4); 5.0 → se asienta 5 sin importar el valor.
        'reprobatoria_asentada' => null,
    ],

    // Extraordinarios y recursamientos. Hay UN solo extraordinario por materia (sobre un ORDINARIO REPROBADA); eso es fijo.
    'regularizacion' => [
        // Calificación mínima aprobatoria del extraordinario. null → la del plantel (Configuración del plantel).
        'extraordinario_calificacion_minima' => null,

        // Además del periodo activo, cuántos periodos anteriores admiten todavía extraordinario
        // (1 → el inmediato anterior, para el examen intersemestral cuando ya se activó el siguiente).
        'extraordinario_periodos_posteriores' => 1,

        // true  → solo recursa quien reprobó también el extraordinario (o un recursamiento anterior).
        // false → también se recursa directo desde el ordinario reprobado.
        'recursamiento_requiere_extraordinario' => true,

        // Máximo de materias reprobadas sin regularizar con el que el alumno todavía se reinscribe (con más, ya no).
        // Por ahora solo se avisa (Regularización y ficha del alumno); la reinscripción lo aplicará.
        'maximo_reprobadas_para_reinscripcion' => 3,
    ],

    // Horarios: altura de cada fila de la cuadrícula (minutos), desde la entrada hasta la salida del turno.
    // Las clases pueden empezar y terminar a cualquier minuto; la cuadrícula solo ordena la vista.
    'horarios' => [
        'minutos_por_bloque' => 50,
    ],

    // Pase de lista por clase (trx_asistencia_clase).
    'asistencia_clase' => [
        // Días naturales, desde la fecha de la clase, en los que el docente todavía corrige su lista.
        // Después, solo Control Escolar (asistencia_clase.corregir) y con motivo.
        'dias_para_corregir' => 3,

        // Porcentaje de asistencia por materia bajo el cual se avisa. Es solo aviso: el derecho a examen lo confirma Control Escolar.
        // Porcentaje = (Asistencia + Retardo) / (clases registradas − faltas justificadas) × 100.
        'minimo_porcentaje' => 80,
    ],

];
