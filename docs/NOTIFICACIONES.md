# Envío de avisos escolares

## Alcance

Los avisos EMAIL originados por incidencias se envían al correo personal de la persona destinataria. El mensaje solo anuncia un aviso y enlaza al portal: no incluye el alumno, el tipo de incidencia ni información de salud. El asunto y mensaje originales permanecen en el portal.

La tutela del alumno y la preferencia `recibe_notificaciones` se vuelven a consultar justo antes de enviar. También se comprueba que el tipo de incidencia todavía permita notificar. No se utiliza `users.email` ni se admiten direcciones del dominio configurado para las cuentas de SIGEs.

APP y WHATSAPP no se procesan. La recuperación de contraseña conserva su flujo existente.

## Cambio de esquema

Se agregó una referencia opcional `com_notificacion.trx_incidencia_id`, con clave foránea e índice. Los avisos nuevos de incidencias guardan su origen. No se intenta deducir el origen de avisos históricos a partir del texto: sin referencia quedan fuera del envío automático.

Aplicar **una sola** alternativa antes de utilizar el código nuevo para registrar incidencias:

1. Ejecutar `database/sql/2026_11_15_notificaciones.sql` en la base correcta, como dueño de las tablas. El SQL incluye la transacción y el registro de la migración.
2. Si la cuenta de Laravel puede alterar el esquema, ejecutar únicamente:

```powershell
php artisan migrate --path=database/migrations/2026_11_15_000000_agregar_incidencia_com_notificacion.php
```

No ejecutar ambos procedimientos ni ejecutar todas las migraciones pendientes. El dump base se conserva; la migración es el cambio incremental. Después de aplicarla, el responsable del esquema puede regenerar el dump con su procedimiento habitual.

Los registros en `com_notificacion_intento` ahora se insertan y actualizan; comprobar que la cuenta de la aplicación tenga esos privilegios y acceso a su secuencia. No se modifican privilegios desde la aplicación.

## Configuración y activación

El envío está desactivado por omisión. Configurar en el entorno del servidor:

```dotenv
SIGES_NOTIFICACIONES_HABILITADAS=true
QUEUE_CONNECTION=database
APP_URL=https://direccion-real-del-plantel
MAIL_HOST=servidor-smtp-del-plantel
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=usuario-del-proveedor
MAIL_PASSWORD=secreto-del-proveedor
MAIL_FROM_ADDRESS=correo-autorizado-del-plantel
MAIL_FROM_NAME="SIGEs"
```

Usar los valores indicados por el proveedor, incluido esquema y puerto (por ejemplo, `smtps` para TLS implícito cuando corresponda). Nunca guardar credenciales reales en el repositorio. El módulo selecciona explícitamente el mailer `smtp`; no depende de `MAIL_MAILER` ni acredita transportes log/array/failover como entregas.

Después de configurar el entorno, renovar la caché de configuración si se utiliza y reiniciar los workers. Mantener activos:

```powershell
php artisan queue:work database --queue=notificaciones --sleep=3 --tries=1 --timeout=60
```

El programador externo debe ejecutar `php artisan schedule:run` cada minuto. Para desarrollo puede utilizarse `php artisan schedule:work`. La tarea `siges:programar-notificaciones` selecciona hasta 100 candidatos por ejecución. La consola manual es:

```powershell
php artisan siges:programar-notificaciones
```

Los procesos deben supervisarse y reiniciarse al arrancar el servidor. El worker genérico de `composer dev` no escucha esta cola dedicada. Mantener `retry_after` de la conexión de cola por encima del timeout del worker (el valor predeterminado de database es 90 segundos). El timeout SMTP es 20 segundos.

Antes de habilitar en producción, verificar con un destinatario de prueba autorizado la entrega y la URL del portal. La activación y el correo real no se ejecutaron como parte de la implementación.

## Estados, concurrencia y reintentos

- Un aviso LEIDA, ENVIADA, de otro canal o sin origen verificable no inicia un envío.
- Un job único evita acumulación de duplicados; el bloqueo de fila y la reserva persistente impiden que dos jobs envíen simultáneamente el mismo aviso.
- La reserva se confirma antes de SMTP. La operación de envío bloquea el aviso y la tutela durante su comprobación y entrega. Una lectura confirmada antes del envío prevalece; si el envío ya comenzó, la lectura espera su finalización.
- SMTP aceptado registra un intento exitoso y ENVIADA. Significa aceptación del servidor, no entrega garantizada en bandeja ni lectura del correo.
- Un rechazo SMTP explícito permite hasta tres intentos por ciclo, separados por al menos cinco minutos. La tarea programada decide los reintentos; el worker no repite el job indiscriminadamente.
- Un corte de conexión, timeout, cancelación o error sin rechazo inequívoco deja entrega incierta. No hay reintento automático.
- Si el proceso termina inesperadamente, la reserva queda «En proceso». Tampoco se reenvía automáticamente.
- Los errores por correo inválido, tutela o preferencia no generan ciclos automáticos. Corregir la causa y autorizar un reintento administrativo.
- No se almacenan mensajes crudos de excepciones SMTP ni credenciales en los resultados o en la bitácora.

SMTP y PostgreSQL no comparten una transacción. No se promete entrega exactamente una vez ante todos los fallos externos; los resultados inciertos requieren comprobación con el proveedor. Si la escritura final falla tras enviar, la reserva persistente evita repetir el mensaje automáticamente.

## Pantallas y permisos

Administración → Notificaciones permite filtrar por estado, consultar el historial y programar un nuevo ciclo de intentos. Reutiliza `usuarios.administrar`; no crea permisos ni modifica roles. Las rutas de consulta y reintento exigen ese permiso, sesión y contraseña actualizada.

El reintento requiere motivo. Para una entrega incierta o reserva interrumpida deben transcurrir cinco minutos y el administrador debe confirmar que comprobó con el proveedor que no hubo entrega. No reintentar si el proveedor confirma que sí se entregó. Los reintentos manuales quedan como registros adicionales del historial y reinician el límite de tres intentos del ciclo; no borran intentos anteriores.

La lectura del aviso puede sustituir ENVIADA por LEIDA. El resultado del envío sigue disponible en los intentos. Todas las reservas, resultados, reprogramaciones y cambios de estado se auditan; los envíos de consola se atribuyen al usuario Sistema.

## Verificación

```powershell
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php --no-progress tests/Feature/NotificacionesTest.php
php artisan route:list --path=notificaciones
php artisan view:cache
php artisan view:clear
```

La suite nueva utiliza exclusivamente SQLite en memoria, fixtures mínimos y transporte simulado. Comprueba estados, tutela, preferencia, correo personal, idempotencia secuencial, lectura entre reserva y envío, errores SMTP, límites, interrupciones, reprogramación, permisos y selección de la cola. No ejecuta migraciones sobre la base de desarrollo ni envía correos. No implica una prueba de concurrencia real en PostgreSQL ni sustituye la comprobación SMTP en el entorno de despliegue. La suite antigua de autenticación sigue fuera de este entregable.
