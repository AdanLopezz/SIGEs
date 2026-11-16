# Egreso — implementación y comprobación

Fecha: 16/11/2026.

## Estado

Módulo implementado con las decisiones del plantel confirmadas en esta conversación. Se reutilizan las tablas y permisos existentes; no requiere migraciones, cambios de paleta ni configuración de correo.

## Implementado

- Entrada existente Vinculación → Egresados, protegida por `egreso.administrar`.
- Listas de candidatos y egresados, filtros de alumno/matrícula, carrera, periodo de último semestre y grupo; paginación compartida.
- Candidatos: alumnos activos sin egreso, inscritos en un grupo de último semestre de su carrera cuyo periodo ya inició. Es una candidatura, no una acreditación automática.
- Revisión de materias sin resultado, evaluaciones sin cerrar, grupos sin materias y adeudos sin regularizar, reutilizando las reglas actuales de extraordinarios y recursamientos.
- Servicio social y prácticas profesionales: convenio, fechas, estado, horas y motivo en bitácora. Se actualiza un registro por alumno y tipo, conservando los cambios auditados.
- Validación de convenios no cancelados, de tipo compatible (o GENERAL), que cubran las fechas del proceso. Se admiten convenios vencidos para documentar procesos históricos dentro de sus fechas.
- Exportación CSV con protección de fórmulas e impresión mediante el layout existente.
- Reutilización de componentes Blade, clases de tablas, formularios, tarjetas, avisos y estados. No se modifica la paleta ni se agregan hojas de estilos.
- Uso de `trx_egreso` y `trx_proceso_egreso` existentes, sin migraciones ni permisos nuevos.
- Acceso desde la ficha del alumno y desde los candidatos del resumen del cambio de periodo.
- Confirmación transaccional con bloqueo del alumno, inscripciones, evaluaciones y resultados; prevención de duplicados.
- Captura del promedio según historial oficial y confirmación expresa de su revisión; fecha no futura ni anterior a la inscripción o al cierre de resultados.
- Cambio del alumno a Egresado, cierre de su inscripción final con el estado `EGRESADO` y desactivación de credenciales QR. Se conservan calificaciones, inscripciones, cuenta y acceso al historial del portal.
- Seguimiento separado de certificado entregado y titulación, con motivo en bitácora. El egreso no los marca automáticamente.

## Reglas acordadas

1. Solo la situación académica bloquea el egreso. Servicio social y prácticas son informativos y pueden actualizarse después del egreso. No se fijan mínimos institucionales de horas; para marcar un proceso concluido se requiere al menos una hora acreditada y una fecha de fin no futura.
2. Administrador y Control Escolar confirman egreso y actualizan certificado/titulación. Vinculación consulta y gestiona procesos. Se exige `egreso.administrar` y, para las operaciones formales, el Gate `egreso-confirmar` comprueba además el rol.
3. El promedio se captura entre 0 y 10, con hasta dos decimales, según el historial oficial. No se deduce una fórmula de promedios, extraordinarios o recursamientos.

La revisión académica se limita a lo registrado; no certifica equivalencias ni materias ausentes del historial. Por eso Control Escolar debe confirmar el cotejo completo. Se bloquea el registro si hay materias sin resultado, evaluaciones sin cerrar (incluidos recursamientos), adeudos sin regularizar o una inscripción posterior al último semestre revisado. El proceso puede completarse para un último semestre de un periodo anterior; no se obliga a reinscribir al alumno para egresarlo.

El estado `EGRESADO` cabe en la columna existente de inscripciones. Evita que la inscripción final siga participando como INSCRITO en pases de lista, captura ordinaria y cierres diarios. Las demás inscripciones históricas se conservan. El módulo no anula egresos ni modifica su fecha/promedio después de confirmarlos; tampoco emite certificados oficiales o títulos.

## Flujo de comprobación manual

1. Ingresar como Control Escolar o Administrador. Abrir Vinculación → Egresados (o Egreso y procesos desde la ficha del alumno).
2. Seleccionar el periodo del último semestre, carrera y grupo. Revisar un candidato activo.
3. Comprobar que aparezcan sus pendientes reales. Una materia reprobada o un resultado sin cerrar debe impedir la confirmación, también si se intenta enviar el formulario directamente.
4. Resolver los pendientes mediante los módulos académicos existentes. Recargar la revisión.
5. Capturar fecha, promedio oficial y motivo; marcar la confirmación del historial. No es necesario registrar servicio social o prácticas para egresar.
6. Confirmar. Verificar el estado Egresado, inscripción final EGRESADO, credenciales desactivadas, historial académico conservado y registro de auditoría. Un segundo envío del formulario no debe duplicar el egreso.
7. En la lista Egresados, consultar al alumno y actualizar certificado/titulación con motivo. No se modifican fecha ni promedio.
8. Entrar como Vinculación: consultar y actualizar procesos; no debe poder confirmar egreso ni cambiar certificado/titulación, incluso por URL directa.
9. Probar CSV e impresión con filtros. Un usuario sin `egreso.administrar` no debe acceder a pantallas, acciones ni exportaciones.

Usar datos de prueba: la confirmación real cambia el estado escolar del alumno y no tiene reversión desde la interfaz.

## Despliegue

Subir los cambios por el procedimiento habitual. Las tablas `trx_egreso` y `trx_proceso_egreso`, el catálogo Egresado y el permiso `egreso.administrar` deben existir (están en el esquema y los datos iniciales del proyecto). Si el servidor usa caché de rutas o vistas, renovarla durante el despliegue; no ejecutar migraciones por este entregable. No requiere SMTP ni altera la configuración de notificaciones.

## Verificación de esta parte

```powershell
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php --no-progress tests/Feature/EgresoTest.php
php artisan route:list --path=egreso
php artisan view:cache
php artisan view:clear
```

Verificación conjunta de egreso y notificaciones: 38 pruebas y 854 comprobaciones exitosas. La suite de egreso contiene 20 pruebas. Se utilizan tablas del esquema adaptadas a SQLite en memoria y datos sintéticos; no se altera PostgreSQL. Se comprueban candidatos, pendientes, regularización, roles reales, confirmación HTTP, rollback, duplicados, procesos informativos, documentación, vistas y exportaciones. También se revisan sintaxis PHP, rutas y compilación Blade. No constituye una prueba de concurrencia real en PostgreSQL ni una comprobación visual en navegador del VPS.
