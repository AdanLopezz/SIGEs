-- Alternativa a la migración específica. Ejecutar como dueño de las tablas.
-- No ejecutar ambos procedimientos. No se atribuye un origen a avisos históricos.
BEGIN;
ALTER TABLE public.com_notificacion
    ADD COLUMN trx_incidencia_id bigint NULL,
    ADD CONSTRAINT com_notificacion_trx_incidencia_id_foreign
        FOREIGN KEY (trx_incidencia_id) REFERENCES public.trx_incidencia(id) ON DELETE RESTRICT;
CREATE INDEX com_notificacion_trx_incidencia_id_index ON public.com_notificacion (trx_incidencia_id);
INSERT INTO public.migrations (migration, batch)
SELECT '2026_11_15_000000_agregar_incidencia_com_notificacion', COALESCE(MAX(batch), 0) + 1
FROM public.migrations;
COMMIT;
