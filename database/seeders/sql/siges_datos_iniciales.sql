

INSERT INTO cat_estado_alumno (nombre, permite_inscripcion) VALUES
 ('Activo', TRUE), ('Baja temporal', FALSE), ('Baja definitiva', FALSE), ('Egresado', FALSE)
ON CONFLICT (nombre) DO NOTHING;

INSERT INTO cat_tipo_evaluacion (nombre, orden, aplica_promedio) VALUES
 ('Parcial 1', 1, TRUE), ('Parcial 2', 2, TRUE), ('Parcial 3', 3, TRUE)
ON CONFLICT (nombre) DO NOTHING;

INSERT INTO cat_tipo_asistencia (codigo, nombre, cuenta_como_asistencia) VALUES
 ('A', 'Asistencia', TRUE), ('F', 'Falta', FALSE), ('R', 'Retardo', TRUE), ('J', 'Falta justificada', FALSE)
ON CONFLICT (codigo) DO NOTHING;

INSERT INTO cat_tipo_incidencia (nombre, categoria, nivel_gravedad, notifica_tutor, justifica_falta) VALUES
 ('Incapacidad médica',          'INCAPACIDAD',    'LEVE',     TRUE,  TRUE),
 ('Falta de respeto',            'CONDUCTA',       'MODERADA', TRUE,  FALSE),
 ('Uso indebido del celular',    'CONDUCTA',       'LEVE',     FALSE, FALSE),
 ('Agresión física',             'CONDUCTA',       'GRAVE',    TRUE,  FALSE),
 ('Suspensión',                  'SUSPENSION',     'GRAVE',    TRUE,  FALSE)
ON CONFLICT (nombre) DO NOTHING;

INSERT INTO cat_medio_difusion (nombre) VALUES
 ('Redes sociales'), ('Visita escolar'), ('Recomendación'), ('Página web'), ('Feria vocacional'), ('Otro')
ON CONFLICT (nombre) DO NOTHING;

INSERT INTO cat_estado_oficio (nombre, es_final) VALUES
 ('Emitido', FALSE), ('Recibido', FALSE), ('En trámite', FALSE), ('Atendido', TRUE), ('Archivado', TRUE)
ON CONFLICT (nombre) DO NOTHING;


-- Usuario "Sistema": lo usan los procesos automáticos de Laravel (asistencia diaria,
-- notificaciones, recordatorios) en registrado_por_user_id y en sys_auditoria.
-- Usa la CURP genérica XEXX010101HNEXXXA4 y una contraseña inválida: no puede iniciar sesión.
INSERT INTO per_persona (curp, nombre, primer_apellido, email_personal, telefono, calle_numero, colonia, municipio, codigo_postal)
VALUES ('XEXX010101HNEXXXA4', 'Sistema', 'SIGEs', 'sistema@siges.local', '0000000000', 'N/A', 'N/A', 'N/A', '00000')
ON CONFLICT (curp) DO NOTHING;
INSERT INTO users (per_persona_id, name, email, password, created_at, updated_at)
SELECT id, 'Sistema', 'sistema@siges.local', '!', now(), now() FROM per_persona WHERE curp = 'XEXX010101HNEXXXA4'
ON CONFLICT (email) DO NOTHING;

-- Roles (Spatie, guard web)
INSERT INTO roles (name, guard_name, created_at, updated_at)
SELECT r, 'web', now(), now() FROM unnest(ARRAY[
 'Administrador', 'Director', 'Subdirector', 'Control Escolar', 'Docente', 'Orientador', 'Tutor',
 'Vinculación', 'Prefectura', 'Planeación', 'Alumno', 'Padre de familia']) AS r
ON CONFLICT (name, guard_name) DO NOTHING;

-- Permisos por módulo
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT p, 'web', now(), now() FROM unnest(ARRAY[
 'configuracion.administrar', 'usuarios.administrar',
 'alumnos.ver', 'alumnos.editar', 'alumnos.salud.ver', 'inscripciones.administrar', 'bajas.registrar',
 'horarios.administrar', 'evaluaciones.programar', 'calificaciones.capturar', 'calificaciones.corregir',
 'resultados.cerrar', 'recursamientos.administrar',
 'asistencia_clase.registrar', 'asistencia_clase.corregir',
 'acceso.registrar', 'asistencia.ver', 'incidencias.registrar', 'incidencias.cerrar', 'permisos_salida.administrar', 'archivos.subir',
 'seguimiento.administrar', 'dual.administrar', 'dual.revisar', 'egreso.administrar', 'aspirantes.administrar',
 'actividades.administrar', 'entregas.revisar', 'oficios.administrar', 'normativos.administrar',
 'encuestas.administrar', 'reportes.ver', 'auditoria.ver']) AS p
ON CONFLICT (name, guard_name) DO NOTHING;

-- Permisos de cada rol
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM (VALUES
 ('Administrador', '%'),
 ('Director', 'reportes.ver'), ('Director', 'auditoria.ver'), ('Director', 'alumnos.ver'), ('Director', 'entregas.revisar'),
 ('Director', 'actividades.administrar'), ('Director', 'oficios.administrar'), ('Director', 'incidencias.registrar'),
 ('Subdirector', 'reportes.ver'), ('Subdirector', 'alumnos.ver'), ('Subdirector', 'seguimiento.administrar'),
 ('Subdirector', 'dual.revisar'), ('Subdirector', 'oficios.administrar'), ('Subdirector', 'incidencias.registrar'),
 ('Control Escolar', 'alumnos.%'), ('Control Escolar', 'archivos.subir'), ('Control Escolar', 'inscripciones.administrar'), ('Control Escolar', 'bajas.registrar'),
 ('Control Escolar', 'horarios.administrar'), ('Control Escolar', 'calificaciones.corregir'), ('Control Escolar', 'resultados.cerrar'),
 ('Control Escolar', 'asistencia_clase.corregir'), ('Control Escolar', 'recursamientos.administrar'), ('Control Escolar', 'egreso.administrar'), ('Control Escolar', 'reportes.ver'),
 ('Docente', 'evaluaciones.programar'), ('Docente', 'calificaciones.capturar'), ('Docente', 'asistencia.ver'),
 ('Docente', 'incidencias.registrar'), ('Docente', 'asistencia_clase.registrar'),
 ('Orientador', 'alumnos.ver'), ('Orientador', 'alumnos.salud.ver'), ('Orientador', 'asistencia.ver'),
 ('Orientador', 'incidencias.%'), ('Orientador', 'permisos_salida.administrar'), ('Orientador', 'archivos.subir'), ('Orientador', 'seguimiento.administrar'), ('Orientador', 'reportes.ver'),
 ('Tutor', 'alumnos.ver'), ('Tutor', 'asistencia.ver'), ('Tutor', 'seguimiento.administrar'), ('Tutor', 'incidencias.registrar'),
 ('Vinculación', 'dual.%'), ('Vinculación', 'egreso.administrar'), ('Vinculación', 'aspirantes.administrar'), ('Vinculación', 'reportes.ver'),
 ('Prefectura', 'acceso.registrar'), ('Prefectura', 'permisos_salida.administrar'), ('Prefectura', 'asistencia.ver'), ('Prefectura', 'incidencias.registrar'),
 ('Planeación', 'actividades.administrar'), ('Planeación', 'normativos.administrar'), ('Planeación', 'encuestas.administrar'),
 ('Planeación', 'entregas.revisar'), ('Planeación', 'reportes.ver')
) AS x(rol, patron)
JOIN roles r ON r.name = x.rol AND r.guard_name = 'web'
JOIN permissions p ON p.name LIKE x.patron AND p.guard_name = 'web'
ON CONFLICT DO NOTHING;
-- Alumno y Padre de familia solo consultan su propia información: Laravel lo filtra con políticas (Policies).
