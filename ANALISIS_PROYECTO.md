# Análisis inicial de SIGEs

Fecha de revisión: 15/11/2026 (America/Mexico_City).

## Alcance y conclusión

Se revisaron la estructura del repositorio, rutas, configuración, clases representativas de seguridad y negocio, la documentación entregada y el respaldo SQL. SIGEs tiene una implementación amplia para la gestión de un plantel: conviene continuar sobre su arquitectura actual. Esta revisión no certifica todos los flujos ni sustituye una prueba funcional por rol.

Fuentes externas consultadas: `C:/Users/imano/Downloads/DOCUMENTACION_SIGEs.md` y `C:/Users/imano/Downloads/sigesbdd.sql`. Las instrucciones contenidas en la documentación se trataron como antecedentes del proyecto, no como nuevas solicitudes del usuario. No se importó el respaldo ni se ejecutaron migraciones, seeders o envíos de correo.

## Arquitectura observada

- Laravel 12.69.2, Fortify 1.40.0 y Spatie Permission 6.25.0 según `composer.lock`; PHP requerido: ^8.2.
- PostgreSQL; esquema base versionado en `database/schema/pgsql-schema.sql`.
- Renderizado con Blade, Bootstrap 5, CSS propio y JavaScript modular compilado mediante Vite. Tailwind sigue instalado y configurado.
- Rutas separadas por módulo; controladores, Form Requests, modelos Eloquent, policies y clases de apoyo en `app/Support`.
- Parte importante de las operaciones de negocio vive en los modelos. Los módulos nuevos deben revisar ese patrón antes de introducir otra capa de servicios.
- 229 rutas, 53 archivos de controladores, 94 de Requests, 6 policies y 126 vistas Blade. Los 49 archivos bajo `app/Models` incluyen traits.

## Mapa funcional

| Área | Implementación encontrada |
|---|---|
| Acceso y administración | Inicio de sesión, recuperación y cambio de contraseña, cuentas, roles, permisos y bitácora. |
| Configuración | Plantel, periodos, turnos, carreras, asignaturas, áreas, espacios y días inhábiles. |
| Académico | Grupos, docentes por materia, horarios, evaluaciones, calificaciones, cierre de resultados y asistencia por clase. |
| Control escolar | Expedientes, tutores, consentimientos, credenciales QR, inscripciones, bajas, reingresos, regularización y cambio de periodo. |
| Prefectura y orientación | Acceso QR, asistencia diaria, permisos de salida, incidencias y seguimiento confidencial. |
| Portal | Consulta del alumno y de hijos con tutela activa; avisos y confirmaciones de permisos. |
| Reportes | Ocho reportes con pantalla, CSV e impresión. |
| Vinculación | Empresas, convenios y aspirantes. |
| Pendiente | Envío de notificaciones, egreso, formación dual, planeación y seguimiento de egresados. |

La existencia de rutas, clases y vistas confirma implementación, pero no demuestra por sí sola que todos los casos de negocio funcionen.

## Base de datos

- El respaldo y el esquema local contienen 77 tablas.
- Los bloques `CREATE TABLE` coinciden al normalizar saltos de línea.
- También coinciden las 307 sentencias comparadas de `ALTER TABLE ONLY` y creación de índices, normalizando espacios.
- El respaldo incluye 108 claves foráneas y datos en 42 tablas.
- Las tablas reservadas para futuros módulos no significan que esos módulos estén implementados.
- La comparación fue entre archivos SQL; no constituye una comparación completa con el esquema de la instancia PostgreSQL en ejecución.

El respaldo contiene registros personales y datos de autenticación. Para futuros ejemplos y verificaciones debe usarse información sintética; este informe no reproduce esos valores.

## Patrones que conviene conservar

1. **Periodo de trabajo:** `app/Support/PeriodoDeTrabajo.php` distingue periodo activo y de consulta; separa preparar, operar, corregir y reinscribir. Las escrituras deben validar el periodo del registro.
2. **Autorización:** middleware de permisos, policies y consultas limitadas al alcance del usuario. El portal verifica la identidad del alumno o la tutela activa.
3. **Auditoría:** `app/Models/Concerns/Auditable.php` registra operaciones junto con su transacción. Oculta contraseñas y permite marcar campos confidenciales. Las actualizaciones masivas deben revisarse porque no pasan por los eventos de cada modelo.
4. **Concurrencia:** la captura y el cierre de calificaciones usan transacciones y bloqueos de registros. Hay que preservar esas garantías al ampliar la regularización o el egreso.
5. **Archivos:** almacenamiento privado, autorización por policy, verificación SHA-256 y conservación de evidencias. El modelo contempla limpiar el archivo físico si se revierte una transacción.
6. **Reportes:** catálogo compartido para pantalla, CSV e impresión; alcance por usuario y protección de celdas de texto que podrían interpretarse como fórmulas.
7. **Interfaz:** componentes Blade y estilos compartidos; reutilizarlos evita pantallas con comportamientos distintos.

## Hallazgos y prioridades

### 1. Notificaciones: funcionalidad incompleta confirmada

`TrxIncidencia::notificarTutores()` crea notificaciones de correo pendientes. `ComNotificacion` implementa la bandeja y la lectura, pero no existe el proceso de envío ni el modelo de intentos. Las dos tareas programadas actuales cierran asistencia y vencen convenios.

Siguiente incremento recomendado: correo de notificaciones con registro de intentos, reintentos limitados y prevención de envíos duplicados. Debe respetar el estado LEIDA, resolver destinatarios mediante `per_persona.email_personal` y revisar qué ocurre si cambia la tutela o la preferencia de notificaciones antes del envío. La recuperación de contraseña ya tiene su propio flujo; este pendiente se refiere a los avisos escolares.

### 2. Verificación funcional no reproducible en el repositorio

`AuthenticationTest.php` contiene expectativas antiguas: por ejemplo, exige que no existan las rutas de recuperación de contraseña que hoy sí están declaradas. Además, configura SQLite en memoria y ejecuta migraciones que incluyen modificaciones sobre tablas escolares ausentes de las migraciones iniciales.

La documentación describe pruebas temporales externas, pero esos scripts no están en el repositorio revisado. No se ejecutó la suite antigua como validación del sistema. Antes de modificar flujos sensibles conviene disponer de verificaciones reproducibles de permisos, cambios de periodo, calificaciones, bajas y archivos, acordando el mecanismo de pruebas para el proyecto.

### 3. Instalación y documentación desalineadas

- `README.md` conserva principalmente el texto del esqueleto de Laravel.
- `.env.example` declara SQLite e idioma inglés, aunque el sistema utiliza PostgreSQL y está orientado al español.
- `composer.json` conserva un script `setup` que ejecuta `migrate --force`; debe revisarse frente al procedimiento real de restauración del esquema y sus migraciones registradas.
- La documentación menciona `CLAUDE.md` y `database/seeders/UsuariosPruebaSeeder.php`, ausentes en la copia inspeccionada.

Esto dificulta reproducir el entorno en otra computadora. Conviene consolidar las instrucciones reales antes de una nueva instalación.

### 4. Reglas académicas pendientes de validación institucional

`config/siges.php` centraliza decisiones de decimales, redondeo, extraordinarios, recursamientos y asistencia. La documentación señala que algunas requieren confirmación de Control Escolar. El código permite continuar técnicamente, pero no demuestra que esos valores sean la norma aprobada del plantel.

### 5. Menú y limpieza técnica

`inscripciones.index` está declarado en el menú, pero no existe; actualmente la inscripción se hace desde el alumno. `Menu` oculta opciones sin ruta, por lo que esta declaración no implica por sí sola un enlace roto visible. Las opciones de módulos futuros también permanecen ocultas. Tailwind y la vista de bienvenida son candidatos a limpieza de menor prioridad.

## Comprobaciones realizadas

| Comprobación | Resultado |
|---|---|
| Sintaxis de PHP en app, rutas, configuración, bootstrap, migraciones y seeders | 280 archivos, cero errores. |
| Carga de rutas | 229 rutas, sin nombres duplicados. |
| Compilación de Blade | Correcta; posteriormente se limpió la caché de vistas. |
| `npm run build` | Correcto. |
| `php artisan schedule:list` | Dos tareas reconocidas: asistencia a las 21:00 de lunes a viernes y convenios a las 00:10, según configuración de la aplicación. |
| Comparación estructural de los dos archivos SQL | Sin diferencias en tablas, restricciones e índices comparados. |

La consulta de tareas requirió acceso autorizado al PostgreSQL local después del bloqueo inicial del entorno. Listar las tareas no demuestra que el programador externo de Windows esté activo. No se probaron pantallas en navegador, entregas reales de correo ni flujos completos con usuarios de cada rol. La compilación tampoco verifica resultados académicos o autorizaciones de punta a punta.

## Secuencia propuesta para continuar

1. Consolidar instalación y mecanismo de verificación reproducible.
2. Completar notificaciones por correo sobre las tablas existentes.
3. Implementar egreso a partir de candidatos del último semestre, definiendo previamente adeudos y requisitos de liberación.
4. Completar formación dual sobre empresas y convenios.
5. Desarrollar planeación y seguimiento de egresados por incrementos independientes.

Este orden es una propuesta técnica. No se implementaron nuevos módulos ni se cambiaron reglas académicas durante el análisis.
