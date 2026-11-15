--
-- PostgreSQL database dump
--

\restrict XcO7fw9ldXiLbMn9lpavhfnqAptURRy5cJbYKKFWyq1J9AiFQP2FswdtUFbulSC

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: aca_asignatura; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_asignatura (
    id bigint NOT NULL,
    clave character varying(30) NOT NULL,
    nombre character varying(150) NOT NULL,
    semestre smallint NOT NULL,
    horas_semana smallint NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_asignatura_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_asignatura_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_asignatura_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_asignatura_id_seq OWNED BY public.aca_asignatura.id;


--
-- Name: aca_baja; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_baja (
    id bigint NOT NULL,
    aca_inscripcion_id bigint NOT NULL,
    tipo character varying(10) NOT NULL,
    fecha date NOT NULL,
    motivo text NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_baja_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_baja_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_baja_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_baja_id_seq OWNED BY public.aca_baja.id;


--
-- Name: aca_carrera; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_carrera (
    id bigint NOT NULL,
    clave character varying(30) NOT NULL,
    nombre character varying(150) NOT NULL,
    tipo character varying(12) DEFAULT 'TECNICA'::character varying NOT NULL,
    total_semestres smallint DEFAULT 6 NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_carrera_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_carrera_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_carrera_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_carrera_id_seq OWNED BY public.aca_carrera.id;


--
-- Name: aca_dia_inhabil; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_dia_inhabil (
    id bigint NOT NULL,
    fecha date NOT NULL,
    motivo character varying(150) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_dia_inhabil_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_dia_inhabil_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_dia_inhabil_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_dia_inhabil_id_seq OWNED BY public.aca_dia_inhabil.id;


--
-- Name: aca_evaluacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_evaluacion (
    id bigint NOT NULL,
    aca_grupo_asignatura_id bigint NOT NULL,
    cat_tipo_evaluacion_id bigint NOT NULL,
    peso_porcentaje numeric(5,2) NOT NULL,
    cerrada boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_evaluacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_evaluacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_evaluacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_evaluacion_id_seq OWNED BY public.aca_evaluacion.id;


--
-- Name: aca_grupo_asignatura; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_grupo_asignatura (
    id bigint NOT NULL,
    aca_grupo_escolar_id bigint NOT NULL,
    aca_asignatura_id bigint NOT NULL,
    docente_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_grupo_asignatura_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_grupo_asignatura_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_grupo_asignatura_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_grupo_asignatura_id_seq OWNED BY public.aca_grupo_asignatura.id;


--
-- Name: aca_grupo_escolar; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_grupo_escolar (
    id bigint NOT NULL,
    aca_periodo_escolar_id bigint NOT NULL,
    aca_carrera_id bigint NOT NULL,
    aca_turno_id bigint NOT NULL,
    identificador character varying(20) NOT NULL,
    semestre smallint NOT NULL,
    orientador_id bigint NOT NULL,
    tutor_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_grupo_escolar_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_grupo_escolar_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_grupo_escolar_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_grupo_escolar_id_seq OWNED BY public.aca_grupo_escolar.id;


--
-- Name: aca_horario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_horario (
    id bigint NOT NULL,
    aca_grupo_asignatura_id bigint NOT NULL,
    org_espacio_fisico_id bigint NOT NULL,
    dia_semana smallint NOT NULL,
    hora_inicio time without time zone NOT NULL,
    hora_fin time without time zone NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_horario_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_horario_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_horario_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_horario_id_seq OWNED BY public.aca_horario.id;


--
-- Name: aca_inscripcion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_inscripcion (
    id bigint NOT NULL,
    per_alumno_id bigint NOT NULL,
    aca_grupo_escolar_id bigint NOT NULL,
    estado character varying(12) DEFAULT 'INSCRITO'::character varying NOT NULL,
    fecha_inscripcion date DEFAULT CURRENT_DATE NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_inscripcion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_inscripcion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_inscripcion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_inscripcion_id_seq OWNED BY public.aca_inscripcion.id;


--
-- Name: aca_periodo_escolar; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_periodo_escolar (
    id bigint NOT NULL,
    clave character varying(30) NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin date NOT NULL,
    activo boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_periodo_escolar_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_periodo_escolar_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_periodo_escolar_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_periodo_escolar_id_seq OWNED BY public.aca_periodo_escolar.id;


--
-- Name: aca_recursamiento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_recursamiento (
    id bigint NOT NULL,
    trx_resultado_asignatura_id bigint NOT NULL,
    aca_grupo_asignatura_id bigint NOT NULL,
    fecha_inicio date DEFAULT CURRENT_DATE NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_recursamiento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_recursamiento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_recursamiento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_recursamiento_id_seq OWNED BY public.aca_recursamiento.id;


--
-- Name: aca_turno; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.aca_turno (
    id bigint NOT NULL,
    nombre character varying(20) NOT NULL,
    hora_entrada time without time zone NOT NULL,
    hora_salida time without time zone NOT NULL,
    tolerancia_minutos smallint DEFAULT 10 NOT NULL,
    limite_retardo_min smallint DEFAULT 30 NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: aca_turno_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.aca_turno_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aca_turno_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.aca_turno_id_seq OWNED BY public.aca_turno.id;


--
-- Name: adm_actividad; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_actividad (
    id bigint NOT NULL,
    org_area_id bigint NOT NULL,
    titulo character varying(200) NOT NULL,
    descripcion text NOT NULL,
    fecha_inicio timestamp(0) without time zone NOT NULL,
    fecha_limite timestamp(0) without time zone NOT NULL,
    recordatorio_dias_antes smallint DEFAULT 2 NOT NULL,
    requiere_entrega boolean DEFAULT false NOT NULL,
    estado character varying(12) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    creado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_actividad_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_actividad_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_actividad_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_actividad_id_seq OWNED BY public.adm_actividad.id;


--
-- Name: adm_actividad_responsable; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_actividad_responsable (
    adm_actividad_id bigint NOT NULL,
    user_id bigint NOT NULL,
    concluida boolean DEFAULT false NOT NULL
);


--
-- Name: adm_documento_normativo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_documento_normativo (
    id bigint NOT NULL,
    titulo character varying(200) NOT NULL,
    tipo character varying(12) NOT NULL,
    archivo_id bigint NOT NULL,
    vigente boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_documento_normativo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_documento_normativo_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_documento_normativo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_documento_normativo_id_seq OWNED BY public.adm_documento_normativo.id;


--
-- Name: adm_encuesta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_encuesta (
    id bigint NOT NULL,
    titulo character varying(200) NOT NULL,
    dirigida_a character varying(10) NOT NULL,
    fecha_apertura timestamp(0) without time zone NOT NULL,
    fecha_cierre timestamp(0) without time zone NOT NULL,
    anonima boolean DEFAULT true NOT NULL,
    creado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_encuesta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_encuesta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_encuesta_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_encuesta_id_seq OWNED BY public.adm_encuesta.id;


--
-- Name: adm_encuesta_pregunta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_encuesta_pregunta (
    id bigint NOT NULL,
    adm_encuesta_id bigint NOT NULL,
    orden smallint NOT NULL,
    texto text NOT NULL,
    tipo_respuesta character varying(10) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_encuesta_pregunta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_encuesta_pregunta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_encuesta_pregunta_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_encuesta_pregunta_id_seq OWNED BY public.adm_encuesta_pregunta.id;


--
-- Name: adm_encuesta_respuesta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_encuesta_respuesta (
    id bigint NOT NULL,
    adm_encuesta_pregunta_id bigint NOT NULL,
    per_persona_id bigint NOT NULL,
    respuesta character varying(500) NOT NULL,
    respondida_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_encuesta_respuesta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_encuesta_respuesta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_encuesta_respuesta_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_encuesta_respuesta_id_seq OWNED BY public.adm_encuesta_respuesta.id;


--
-- Name: adm_entrega_producto; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_entrega_producto (
    id bigint NOT NULL,
    adm_actividad_id bigint NOT NULL,
    entregado_por_user_id bigint NOT NULL,
    version smallint DEFAULT 1 NOT NULL,
    archivo_id bigint NOT NULL,
    fecha_entrega timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    estado_revision character varying(17) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_entrega_producto_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_entrega_producto_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_entrega_producto_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_entrega_producto_id_seq OWNED BY public.adm_entrega_producto.id;


--
-- Name: adm_historial_oficio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_historial_oficio (
    id bigint NOT NULL,
    adm_oficio_id bigint NOT NULL,
    cat_estado_oficio_id bigint NOT NULL,
    user_id bigint NOT NULL,
    comentarios text NOT NULL,
    fecha_cambio timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_historial_oficio_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_historial_oficio_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_historial_oficio_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_historial_oficio_id_seq OWNED BY public.adm_historial_oficio.id;


--
-- Name: adm_oficio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_oficio (
    id bigint NOT NULL,
    numero_oficio character varying(50) NOT NULL,
    asunto text NOT NULL,
    org_area_origen_id bigint NOT NULL,
    org_area_destino_id bigint NOT NULL,
    cat_estado_oficio_id bigint NOT NULL,
    responsable_user_id bigint NOT NULL,
    fecha_emision date DEFAULT CURRENT_DATE NOT NULL,
    fecha_limite date NOT NULL,
    archivo_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_oficio_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_oficio_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_oficio_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_oficio_id_seq OWNED BY public.adm_oficio.id;


--
-- Name: adm_procedimiento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_procedimiento (
    id bigint NOT NULL,
    org_area_id bigint NOT NULL,
    clave character varying(20) NOT NULL,
    nombre character varying(200) NOT NULL,
    objetivo text NOT NULL,
    pasos text NOT NULL,
    version character varying(10) DEFAULT '1.0'::character varying NOT NULL,
    vigente boolean DEFAULT true NOT NULL,
    fecha_actualizacion date DEFAULT CURRENT_DATE NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_procedimiento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_procedimiento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_procedimiento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_procedimiento_id_seq OWNED BY public.adm_procedimiento.id;


--
-- Name: adm_programa_avance; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_programa_avance (
    id bigint NOT NULL,
    adm_programa_institucional_id bigint NOT NULL,
    fecha date DEFAULT CURRENT_DATE NOT NULL,
    porcentaje numeric(5,2) NOT NULL,
    nota text NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_programa_avance_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_programa_avance_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_programa_avance_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_programa_avance_id_seq OWNED BY public.adm_programa_avance.id;


--
-- Name: adm_programa_institucional; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adm_programa_institucional (
    id bigint NOT NULL,
    aca_periodo_escolar_id bigint NOT NULL,
    nombre character varying(150) NOT NULL,
    descripcion text NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: adm_programa_institucional_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adm_programa_institucional_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adm_programa_institucional_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adm_programa_institucional_id_seq OWNED BY public.adm_programa_institucional.id;


--
-- Name: archivo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.archivo (
    id bigint NOT NULL,
    disco character varying(20) DEFAULT 'local'::character varying NOT NULL,
    ruta character varying(255) NOT NULL,
    nombre_original character varying(255) NOT NULL,
    tipo_mime character varying(100) NOT NULL,
    tamano_bytes bigint NOT NULL,
    sha256 character(64) NOT NULL,
    subido_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: archivo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.archivo_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: archivo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.archivo_id_seq OWNED BY public.archivo.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cat_estado_alumno; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cat_estado_alumno (
    id bigint NOT NULL,
    nombre character varying(50) NOT NULL,
    permite_inscripcion boolean DEFAULT true NOT NULL
);


--
-- Name: cat_estado_alumno_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cat_estado_alumno_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cat_estado_alumno_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cat_estado_alumno_id_seq OWNED BY public.cat_estado_alumno.id;


--
-- Name: cat_estado_oficio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cat_estado_oficio (
    id bigint NOT NULL,
    nombre character varying(50) NOT NULL,
    es_final boolean DEFAULT false NOT NULL
);


--
-- Name: cat_estado_oficio_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cat_estado_oficio_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cat_estado_oficio_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cat_estado_oficio_id_seq OWNED BY public.cat_estado_oficio.id;


--
-- Name: cat_medio_difusion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cat_medio_difusion (
    id bigint NOT NULL,
    nombre character varying(60) NOT NULL
);


--
-- Name: cat_medio_difusion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cat_medio_difusion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cat_medio_difusion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cat_medio_difusion_id_seq OWNED BY public.cat_medio_difusion.id;


--
-- Name: cat_tipo_asistencia; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cat_tipo_asistencia (
    id bigint NOT NULL,
    codigo character varying(5) NOT NULL,
    nombre character varying(50) NOT NULL,
    cuenta_como_asistencia boolean DEFAULT true NOT NULL
);


--
-- Name: cat_tipo_asistencia_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cat_tipo_asistencia_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cat_tipo_asistencia_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cat_tipo_asistencia_id_seq OWNED BY public.cat_tipo_asistencia.id;


--
-- Name: cat_tipo_evaluacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cat_tipo_evaluacion (
    id bigint NOT NULL,
    nombre character varying(50) NOT NULL,
    orden smallint NOT NULL,
    aplica_promedio boolean DEFAULT true NOT NULL
);


--
-- Name: cat_tipo_evaluacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cat_tipo_evaluacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cat_tipo_evaluacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cat_tipo_evaluacion_id_seq OWNED BY public.cat_tipo_evaluacion.id;


--
-- Name: cat_tipo_incidencia; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cat_tipo_incidencia (
    id bigint NOT NULL,
    nombre character varying(80) NOT NULL,
    categoria character varying(20) NOT NULL,
    nivel_gravedad character varying(10) DEFAULT 'LEVE'::character varying NOT NULL,
    notifica_tutor boolean DEFAULT true NOT NULL,
    justifica_falta boolean DEFAULT false NOT NULL
);


--
-- Name: cat_tipo_incidencia_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cat_tipo_incidencia_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cat_tipo_incidencia_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cat_tipo_incidencia_id_seq OWNED BY public.cat_tipo_incidencia.id;


--
-- Name: com_notificacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.com_notificacion (
    id bigint NOT NULL,
    per_persona_id bigint NOT NULL,
    canal character varying(8) NOT NULL,
    asunto character varying(200) NOT NULL,
    mensaje text NOT NULL,
    estado character varying(9) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: com_notificacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.com_notificacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: com_notificacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.com_notificacion_id_seq OWNED BY public.com_notificacion.id;


--
-- Name: com_notificacion_intento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.com_notificacion_intento (
    id bigint NOT NULL,
    com_notificacion_id bigint NOT NULL,
    fecha_intento timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    exitoso boolean NOT NULL,
    resultado character varying(255) NOT NULL
);


--
-- Name: com_notificacion_intento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.com_notificacion_intento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: com_notificacion_intento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.com_notificacion_intento_id_seq OWNED BY public.com_notificacion_intento.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: org_area; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.org_area (
    id bigint NOT NULL,
    nombre character varying(150) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: org_area_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.org_area_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: org_area_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.org_area_id_seq OWNED BY public.org_area.id;


--
-- Name: org_convenio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.org_convenio (
    id bigint NOT NULL,
    org_empresa_id bigint NOT NULL,
    numero character varying(40) NOT NULL,
    tipo character varying(25) NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin date NOT NULL,
    estado character varying(10) DEFAULT 'VIGENTE'::character varying NOT NULL,
    archivo_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: org_convenio_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.org_convenio_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: org_convenio_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.org_convenio_id_seq OWNED BY public.org_convenio.id;


--
-- Name: org_empresa; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.org_empresa (
    id bigint NOT NULL,
    nombre character varying(150) NOT NULL,
    rfc character varying(13) NOT NULL,
    contacto_nombre character varying(150) NOT NULL,
    contacto_telefono character varying(20) NOT NULL,
    contacto_correo character varying(150) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: org_empresa_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.org_empresa_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: org_empresa_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.org_empresa_id_seq OWNED BY public.org_empresa.id;


--
-- Name: org_espacio_fisico; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.org_espacio_fisico (
    id bigint NOT NULL,
    nombre character varying(60) NOT NULL,
    tipo character varying(12) DEFAULT 'AULA'::character varying NOT NULL,
    capacidad smallint DEFAULT 40 NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: org_espacio_fisico_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.org_espacio_fisico_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: org_espacio_fisico_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.org_espacio_fisico_id_seq OWNED BY public.org_espacio_fisico.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: per_alumno; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.per_alumno (
    id bigint NOT NULL,
    per_persona_id bigint NOT NULL,
    aca_carrera_id bigint NOT NULL,
    cat_estado_alumno_id bigint NOT NULL,
    matricula character varying(30) NOT NULL,
    email_institucional character varying(150) NOT NULL,
    fecha_ingreso date NOT NULL,
    expediente_archivo_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: per_alumno_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.per_alumno_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: per_alumno_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.per_alumno_id_seq OWNED BY public.per_alumno.id;


--
-- Name: per_alumno_salud; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.per_alumno_salud (
    id bigint NOT NULL,
    per_alumno_id bigint NOT NULL,
    nss character varying(11) NOT NULL,
    tipo_sangre character varying(3) NOT NULL,
    alergias text DEFAULT 'NINGUNA'::text NOT NULL,
    condiciones_medicas text DEFAULT 'NINGUNA'::text NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: per_alumno_salud_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.per_alumno_salud_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: per_alumno_salud_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.per_alumno_salud_id_seq OWNED BY public.per_alumno_salud.id;


--
-- Name: per_consentimiento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.per_consentimiento (
    id bigint NOT NULL,
    per_tutor_alumno_id bigint NOT NULL,
    tipo character varying(20) NOT NULL,
    otorgado boolean NOT NULL,
    fecha date DEFAULT CURRENT_DATE NOT NULL,
    archivo_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: per_consentimiento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.per_consentimiento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: per_consentimiento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.per_consentimiento_id_seq OWNED BY public.per_consentimiento.id;


--
-- Name: per_credencial_acceso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.per_credencial_acceso (
    id bigint NOT NULL,
    per_alumno_id bigint NOT NULL,
    token_hash character(64) NOT NULL,
    fecha_emision timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    activa boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: per_credencial_acceso_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.per_credencial_acceso_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: per_credencial_acceso_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.per_credencial_acceso_id_seq OWNED BY public.per_credencial_acceso.id;


--
-- Name: per_persona; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.per_persona (
    id bigint NOT NULL,
    curp character varying(18) NOT NULL,
    nombre character varying(100) NOT NULL,
    primer_apellido character varying(100) NOT NULL,
    segundo_apellido character varying(100) DEFAULT ''::character varying NOT NULL,
    email_personal character varying(150) NOT NULL,
    telefono character varying(20) NOT NULL,
    calle_numero character varying(150) NOT NULL,
    colonia character varying(100) NOT NULL,
    municipio character varying(100) NOT NULL,
    codigo_postal character varying(5) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: per_persona_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.per_persona_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: per_persona_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.per_persona_id_seq OWNED BY public.per_persona.id;


--
-- Name: per_personal; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.per_personal (
    id bigint NOT NULL,
    per_persona_id bigint NOT NULL,
    org_area_id bigint NOT NULL,
    numero_empleado character varying(50) NOT NULL,
    puesto character varying(80) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: per_personal_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.per_personal_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: per_personal_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.per_personal_id_seq OWNED BY public.per_personal.id;


--
-- Name: per_tutor_alumno; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.per_tutor_alumno (
    id bigint NOT NULL,
    per_alumno_id bigint NOT NULL,
    per_persona_id bigint NOT NULL,
    parentesco character varying(12) NOT NULL,
    es_contacto_emergencia boolean DEFAULT false NOT NULL,
    recibe_notificaciones boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: per_tutor_alumno_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.per_tutor_alumno_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: per_tutor_alumno_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.per_tutor_alumno_id_seq OWNED BY public.per_tutor_alumno.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) DEFAULT 'web'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) DEFAULT 'web'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: sys_auditoria; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sys_auditoria (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    tabla_afectada character varying(64) NOT NULL,
    registro_id bigint NOT NULL,
    accion character varying(6) NOT NULL,
    motivo character varying(255) NOT NULL,
    ip_address character varying(45) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: sys_auditoria_detalle; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sys_auditoria_detalle (
    id bigint NOT NULL,
    sys_auditoria_id bigint NOT NULL,
    campo character varying(64) NOT NULL,
    valor_anterior text DEFAULT ''::text NOT NULL,
    valor_nuevo text DEFAULT ''::text NOT NULL
);


--
-- Name: sys_auditoria_detalle_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sys_auditoria_detalle_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sys_auditoria_detalle_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sys_auditoria_detalle_id_seq OWNED BY public.sys_auditoria_detalle.id;


--
-- Name: sys_auditoria_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sys_auditoria_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sys_auditoria_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sys_auditoria_id_seq OWNED BY public.sys_auditoria.id;


--
-- Name: sys_configuracion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sys_configuracion (
    id bigint DEFAULT 1 NOT NULL,
    clave_cct character varying(20) NOT NULL,
    nombre character varying(200) NOT NULL,
    nombre_corto character varying(50) NOT NULL,
    calificacion_minima numeric(4,2) DEFAULT 6 NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_asistencia_clase; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_asistencia_clase (
    id bigint NOT NULL,
    aca_inscripcion_id bigint NOT NULL,
    aca_grupo_asignatura_id bigint NOT NULL,
    cat_tipo_asistencia_id bigint NOT NULL,
    fecha date DEFAULT CURRENT_DATE NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_asistencia_clase_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_asistencia_clase_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_asistencia_clase_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_asistencia_clase_id_seq OWNED BY public.trx_asistencia_clase.id;


--
-- Name: trx_asistencia_diaria; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_asistencia_diaria (
    id bigint NOT NULL,
    aca_inscripcion_id bigint NOT NULL,
    fecha date NOT NULL,
    estado character varying(12) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_asistencia_diaria_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_asistencia_diaria_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_asistencia_diaria_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_asistencia_diaria_id_seq OWNED BY public.trx_asistencia_diaria.id;


--
-- Name: trx_aspirante; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_aspirante (
    id bigint NOT NULL,
    per_persona_id bigint NOT NULL,
    aca_carrera_id bigint NOT NULL,
    cat_medio_difusion_id bigint NOT NULL,
    escuela_procedencia character varying(150) NOT NULL,
    ciclo_ingreso character varying(9) NOT NULL,
    estado character varying(12) DEFAULT 'PROSPECTO'::character varying NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_aspirante_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_aspirante_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_aspirante_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_aspirante_id_seq OWNED BY public.trx_aspirante.id;


--
-- Name: trx_calificacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_calificacion (
    id bigint NOT NULL,
    aca_inscripcion_id bigint NOT NULL,
    aca_evaluacion_id bigint NOT NULL,
    valor numeric(4,2) NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_calificacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_calificacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_calificacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_calificacion_id_seq OWNED BY public.trx_calificacion.id;


--
-- Name: trx_egreso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_egreso (
    id bigint NOT NULL,
    per_alumno_id bigint NOT NULL,
    fecha_egreso date NOT NULL,
    promedio_general numeric(4,2) NOT NULL,
    certificado_entregado boolean DEFAULT false NOT NULL,
    titulado boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_egreso_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_egreso_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_egreso_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_egreso_id_seq OWNED BY public.trx_egreso.id;


--
-- Name: trx_incidencia; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_incidencia (
    id bigint NOT NULL,
    aca_inscripcion_id bigint NOT NULL,
    per_alumno_id bigint NOT NULL,
    cat_tipo_incidencia_id bigint NOT NULL,
    descripcion text NOT NULL,
    fecha_inicio date DEFAULT CURRENT_DATE NOT NULL,
    fecha_fin date,
    archivo_id bigint,
    estado character varying(15) DEFAULT 'ABIERTA'::character varying NOT NULL,
    reportado_por_user_id bigint NOT NULL,
    resolucion text,
    resuelta_por_user_id bigint,
    fecha_resolucion date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: trx_incidencia_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_incidencia_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_incidencia_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_incidencia_id_seq OWNED BY public.trx_incidencia.id;


--
-- Name: trx_permiso_confirmacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_permiso_confirmacion (
    id bigint NOT NULL,
    trx_permiso_salida_id bigint NOT NULL,
    per_tutor_alumno_id bigint NOT NULL,
    decision character varying(10) NOT NULL,
    metodo character varying(10) NOT NULL,
    evidencia character varying(255) NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    confirmado_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_permiso_confirmacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_permiso_confirmacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_permiso_confirmacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_permiso_confirmacion_id_seq OWNED BY public.trx_permiso_confirmacion.id;


--
-- Name: trx_permiso_salida; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_permiso_salida (
    id bigint NOT NULL,
    aca_inscripcion_id bigint NOT NULL,
    motivo text NOT NULL,
    salida_prevista_at timestamp(0) without time zone NOT NULL,
    estado character varying(10) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    solicitado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_permiso_salida_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_permiso_salida_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_permiso_salida_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_permiso_salida_id_seq OWNED BY public.trx_permiso_salida.id;


--
-- Name: trx_proceso_egreso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_proceso_egreso (
    id bigint NOT NULL,
    per_alumno_id bigint NOT NULL,
    tipo character varying(25) NOT NULL,
    org_convenio_id bigint NOT NULL,
    estado character varying(12) DEFAULT 'EN_PROCESO'::character varying NOT NULL,
    horas_acreditadas smallint DEFAULT 0 NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin date NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_proceso_egreso_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_proceso_egreso_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_proceso_egreso_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_proceso_egreso_id_seq OWNED BY public.trx_proceso_egreso.id;


--
-- Name: trx_programa_dual; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_programa_dual (
    id bigint NOT NULL,
    aca_inscripcion_id bigint NOT NULL,
    org_convenio_id bigint NOT NULL,
    tutor_id bigint NOT NULL,
    supervisor_empresa character varying(150) NOT NULL,
    estado character varying(12) DEFAULT 'EN_PROCESO'::character varying NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin date NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_programa_dual_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_programa_dual_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_programa_dual_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_programa_dual_id_seq OWNED BY public.trx_programa_dual.id;


--
-- Name: trx_registro_acceso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_registro_acceso (
    id bigint NOT NULL,
    per_credencial_acceso_id bigint NOT NULL,
    tipo_movimiento character varying(7) NOT NULL,
    metodo character varying(6) DEFAULT 'QR'::character varying NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    fecha_hora timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_registro_acceso_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_registro_acceso_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_registro_acceso_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_registro_acceso_id_seq OWNED BY public.trx_registro_acceso.id;


--
-- Name: trx_reporte_dual; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_reporte_dual (
    id bigint NOT NULL,
    trx_programa_dual_id bigint NOT NULL,
    numero_reporte smallint NOT NULL,
    fecha_reporte date NOT NULL,
    horas_cubiertas smallint DEFAULT 0 NOT NULL,
    actividades_realizadas text NOT NULL,
    archivo_id bigint NOT NULL,
    estado_revision character varying(17) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_reporte_dual_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_reporte_dual_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_reporte_dual_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_reporte_dual_id_seq OWNED BY public.trx_reporte_dual.id;


--
-- Name: trx_resultado_asignatura; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_resultado_asignatura (
    id bigint NOT NULL,
    aca_inscripcion_id bigint NOT NULL,
    aca_grupo_asignatura_id bigint NOT NULL,
    oportunidad character varying(15) DEFAULT 'ORDINARIO'::character varying NOT NULL,
    calificacion numeric(4,2) NOT NULL,
    estado character varying(10) NOT NULL,
    fecha_cierre date DEFAULT CURRENT_DATE NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_resultado_asignatura_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_resultado_asignatura_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_resultado_asignatura_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_resultado_asignatura_id_seq OWNED BY public.trx_resultado_asignatura.id;


--
-- Name: trx_seguimiento_alumno; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_seguimiento_alumno (
    id bigint NOT NULL,
    aca_inscripcion_id bigint NOT NULL,
    motivo character varying(20) NOT NULL,
    descripcion text NOT NULL,
    riesgo_abandono boolean DEFAULT false NOT NULL,
    estado character varying(15) DEFAULT 'ABIERTO'::character varying NOT NULL,
    fecha_apertura date DEFAULT CURRENT_DATE NOT NULL,
    abierto_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_seguimiento_alumno_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_seguimiento_alumno_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_seguimiento_alumno_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_seguimiento_alumno_id_seq OWNED BY public.trx_seguimiento_alumno.id;


--
-- Name: trx_seguimiento_egresado; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_seguimiento_egresado (
    id bigint NOT NULL,
    trx_egreso_id bigint NOT NULL,
    fecha_registro date DEFAULT CURRENT_DATE NOT NULL,
    situacion character varying(20) NOT NULL,
    actividad_actual character varying(200) NOT NULL,
    relacionado_carrera boolean DEFAULT false NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_seguimiento_egresado_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_seguimiento_egresado_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_seguimiento_egresado_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_seguimiento_egresado_id_seq OWNED BY public.trx_seguimiento_egresado.id;


--
-- Name: trx_seguimiento_nota; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trx_seguimiento_nota (
    id bigint NOT NULL,
    trx_seguimiento_alumno_id bigint NOT NULL,
    fecha date DEFAULT CURRENT_DATE NOT NULL,
    tipo_atencion character varying(20) NOT NULL,
    descripcion text NOT NULL,
    acuerdos text NOT NULL,
    registrado_por_user_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: trx_seguimiento_nota_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trx_seguimiento_nota_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trx_seguimiento_nota_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trx_seguimiento_nota_id_seq OWNED BY public.trx_seguimiento_nota.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    per_persona_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    password_temporal text,
    debe_cambiar_password boolean DEFAULT false NOT NULL
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: aca_asignatura id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_asignatura ALTER COLUMN id SET DEFAULT nextval('public.aca_asignatura_id_seq'::regclass);


--
-- Name: aca_baja id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_baja ALTER COLUMN id SET DEFAULT nextval('public.aca_baja_id_seq'::regclass);


--
-- Name: aca_carrera id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_carrera ALTER COLUMN id SET DEFAULT nextval('public.aca_carrera_id_seq'::regclass);


--
-- Name: aca_dia_inhabil id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_dia_inhabil ALTER COLUMN id SET DEFAULT nextval('public.aca_dia_inhabil_id_seq'::regclass);


--
-- Name: aca_evaluacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_evaluacion ALTER COLUMN id SET DEFAULT nextval('public.aca_evaluacion_id_seq'::regclass);


--
-- Name: aca_grupo_asignatura id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_asignatura ALTER COLUMN id SET DEFAULT nextval('public.aca_grupo_asignatura_id_seq'::regclass);


--
-- Name: aca_grupo_escolar id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_escolar ALTER COLUMN id SET DEFAULT nextval('public.aca_grupo_escolar_id_seq'::regclass);


--
-- Name: aca_horario id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_horario ALTER COLUMN id SET DEFAULT nextval('public.aca_horario_id_seq'::regclass);


--
-- Name: aca_inscripcion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_inscripcion ALTER COLUMN id SET DEFAULT nextval('public.aca_inscripcion_id_seq'::regclass);


--
-- Name: aca_periodo_escolar id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_periodo_escolar ALTER COLUMN id SET DEFAULT nextval('public.aca_periodo_escolar_id_seq'::regclass);


--
-- Name: aca_recursamiento id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_recursamiento ALTER COLUMN id SET DEFAULT nextval('public.aca_recursamiento_id_seq'::regclass);


--
-- Name: aca_turno id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_turno ALTER COLUMN id SET DEFAULT nextval('public.aca_turno_id_seq'::regclass);


--
-- Name: adm_actividad id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_actividad ALTER COLUMN id SET DEFAULT nextval('public.adm_actividad_id_seq'::regclass);


--
-- Name: adm_documento_normativo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_documento_normativo ALTER COLUMN id SET DEFAULT nextval('public.adm_documento_normativo_id_seq'::regclass);


--
-- Name: adm_encuesta id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta ALTER COLUMN id SET DEFAULT nextval('public.adm_encuesta_id_seq'::regclass);


--
-- Name: adm_encuesta_pregunta id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta_pregunta ALTER COLUMN id SET DEFAULT nextval('public.adm_encuesta_pregunta_id_seq'::regclass);


--
-- Name: adm_encuesta_respuesta id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta_respuesta ALTER COLUMN id SET DEFAULT nextval('public.adm_encuesta_respuesta_id_seq'::regclass);


--
-- Name: adm_entrega_producto id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_entrega_producto ALTER COLUMN id SET DEFAULT nextval('public.adm_entrega_producto_id_seq'::regclass);


--
-- Name: adm_historial_oficio id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_historial_oficio ALTER COLUMN id SET DEFAULT nextval('public.adm_historial_oficio_id_seq'::regclass);


--
-- Name: adm_oficio id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_oficio ALTER COLUMN id SET DEFAULT nextval('public.adm_oficio_id_seq'::regclass);


--
-- Name: adm_procedimiento id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_procedimiento ALTER COLUMN id SET DEFAULT nextval('public.adm_procedimiento_id_seq'::regclass);


--
-- Name: adm_programa_avance id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_programa_avance ALTER COLUMN id SET DEFAULT nextval('public.adm_programa_avance_id_seq'::regclass);


--
-- Name: adm_programa_institucional id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_programa_institucional ALTER COLUMN id SET DEFAULT nextval('public.adm_programa_institucional_id_seq'::regclass);


--
-- Name: archivo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.archivo ALTER COLUMN id SET DEFAULT nextval('public.archivo_id_seq'::regclass);


--
-- Name: cat_estado_alumno id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_estado_alumno ALTER COLUMN id SET DEFAULT nextval('public.cat_estado_alumno_id_seq'::regclass);


--
-- Name: cat_estado_oficio id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_estado_oficio ALTER COLUMN id SET DEFAULT nextval('public.cat_estado_oficio_id_seq'::regclass);


--
-- Name: cat_medio_difusion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_medio_difusion ALTER COLUMN id SET DEFAULT nextval('public.cat_medio_difusion_id_seq'::regclass);


--
-- Name: cat_tipo_asistencia id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_tipo_asistencia ALTER COLUMN id SET DEFAULT nextval('public.cat_tipo_asistencia_id_seq'::regclass);


--
-- Name: cat_tipo_evaluacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_tipo_evaluacion ALTER COLUMN id SET DEFAULT nextval('public.cat_tipo_evaluacion_id_seq'::regclass);


--
-- Name: cat_tipo_incidencia id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_tipo_incidencia ALTER COLUMN id SET DEFAULT nextval('public.cat_tipo_incidencia_id_seq'::regclass);


--
-- Name: com_notificacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.com_notificacion ALTER COLUMN id SET DEFAULT nextval('public.com_notificacion_id_seq'::regclass);


--
-- Name: com_notificacion_intento id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.com_notificacion_intento ALTER COLUMN id SET DEFAULT nextval('public.com_notificacion_intento_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: org_area id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_area ALTER COLUMN id SET DEFAULT nextval('public.org_area_id_seq'::regclass);


--
-- Name: org_convenio id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_convenio ALTER COLUMN id SET DEFAULT nextval('public.org_convenio_id_seq'::regclass);


--
-- Name: org_empresa id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_empresa ALTER COLUMN id SET DEFAULT nextval('public.org_empresa_id_seq'::regclass);


--
-- Name: org_espacio_fisico id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_espacio_fisico ALTER COLUMN id SET DEFAULT nextval('public.org_espacio_fisico_id_seq'::regclass);


--
-- Name: per_alumno id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno ALTER COLUMN id SET DEFAULT nextval('public.per_alumno_id_seq'::regclass);


--
-- Name: per_alumno_salud id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno_salud ALTER COLUMN id SET DEFAULT nextval('public.per_alumno_salud_id_seq'::regclass);


--
-- Name: per_consentimiento id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_consentimiento ALTER COLUMN id SET DEFAULT nextval('public.per_consentimiento_id_seq'::regclass);


--
-- Name: per_credencial_acceso id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_credencial_acceso ALTER COLUMN id SET DEFAULT nextval('public.per_credencial_acceso_id_seq'::regclass);


--
-- Name: per_persona id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_persona ALTER COLUMN id SET DEFAULT nextval('public.per_persona_id_seq'::regclass);


--
-- Name: per_personal id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_personal ALTER COLUMN id SET DEFAULT nextval('public.per_personal_id_seq'::regclass);


--
-- Name: per_tutor_alumno id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_tutor_alumno ALTER COLUMN id SET DEFAULT nextval('public.per_tutor_alumno_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: sys_auditoria id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sys_auditoria ALTER COLUMN id SET DEFAULT nextval('public.sys_auditoria_id_seq'::regclass);


--
-- Name: sys_auditoria_detalle id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sys_auditoria_detalle ALTER COLUMN id SET DEFAULT nextval('public.sys_auditoria_detalle_id_seq'::regclass);


--
-- Name: trx_asistencia_clase id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_clase ALTER COLUMN id SET DEFAULT nextval('public.trx_asistencia_clase_id_seq'::regclass);


--
-- Name: trx_asistencia_diaria id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_diaria ALTER COLUMN id SET DEFAULT nextval('public.trx_asistencia_diaria_id_seq'::regclass);


--
-- Name: trx_aspirante id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_aspirante ALTER COLUMN id SET DEFAULT nextval('public.trx_aspirante_id_seq'::regclass);


--
-- Name: trx_calificacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_calificacion ALTER COLUMN id SET DEFAULT nextval('public.trx_calificacion_id_seq'::regclass);


--
-- Name: trx_egreso id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_egreso ALTER COLUMN id SET DEFAULT nextval('public.trx_egreso_id_seq'::regclass);


--
-- Name: trx_incidencia id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_incidencia ALTER COLUMN id SET DEFAULT nextval('public.trx_incidencia_id_seq'::regclass);


--
-- Name: trx_permiso_confirmacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_permiso_confirmacion ALTER COLUMN id SET DEFAULT nextval('public.trx_permiso_confirmacion_id_seq'::regclass);


--
-- Name: trx_permiso_salida id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_permiso_salida ALTER COLUMN id SET DEFAULT nextval('public.trx_permiso_salida_id_seq'::regclass);


--
-- Name: trx_proceso_egreso id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_proceso_egreso ALTER COLUMN id SET DEFAULT nextval('public.trx_proceso_egreso_id_seq'::regclass);


--
-- Name: trx_programa_dual id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_programa_dual ALTER COLUMN id SET DEFAULT nextval('public.trx_programa_dual_id_seq'::regclass);


--
-- Name: trx_registro_acceso id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_registro_acceso ALTER COLUMN id SET DEFAULT nextval('public.trx_registro_acceso_id_seq'::regclass);


--
-- Name: trx_reporte_dual id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_reporte_dual ALTER COLUMN id SET DEFAULT nextval('public.trx_reporte_dual_id_seq'::regclass);


--
-- Name: trx_resultado_asignatura id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_resultado_asignatura ALTER COLUMN id SET DEFAULT nextval('public.trx_resultado_asignatura_id_seq'::regclass);


--
-- Name: trx_seguimiento_alumno id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_alumno ALTER COLUMN id SET DEFAULT nextval('public.trx_seguimiento_alumno_id_seq'::regclass);


--
-- Name: trx_seguimiento_egresado id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_egresado ALTER COLUMN id SET DEFAULT nextval('public.trx_seguimiento_egresado_id_seq'::regclass);


--
-- Name: trx_seguimiento_nota id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_nota ALTER COLUMN id SET DEFAULT nextval('public.trx_seguimiento_nota_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: aca_asignatura aca_asignatura_clave_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_asignatura
    ADD CONSTRAINT aca_asignatura_clave_key UNIQUE (clave);


--
-- Name: aca_asignatura aca_asignatura_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_asignatura
    ADD CONSTRAINT aca_asignatura_pkey PRIMARY KEY (id);


--
-- Name: aca_baja aca_baja_aca_inscripcion_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_baja
    ADD CONSTRAINT aca_baja_aca_inscripcion_id_key UNIQUE (aca_inscripcion_id);


--
-- Name: aca_baja aca_baja_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_baja
    ADD CONSTRAINT aca_baja_pkey PRIMARY KEY (id);


--
-- Name: aca_carrera aca_carrera_clave_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_carrera
    ADD CONSTRAINT aca_carrera_clave_key UNIQUE (clave);


--
-- Name: aca_carrera aca_carrera_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_carrera
    ADD CONSTRAINT aca_carrera_pkey PRIMARY KEY (id);


--
-- Name: aca_dia_inhabil aca_dia_inhabil_fecha_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_dia_inhabil
    ADD CONSTRAINT aca_dia_inhabil_fecha_key UNIQUE (fecha);


--
-- Name: aca_dia_inhabil aca_dia_inhabil_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_dia_inhabil
    ADD CONSTRAINT aca_dia_inhabil_pkey PRIMARY KEY (id);


--
-- Name: aca_evaluacion aca_evaluacion_aca_grupo_asignatura_id_cat_tipo_evaluacion__key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_evaluacion
    ADD CONSTRAINT aca_evaluacion_aca_grupo_asignatura_id_cat_tipo_evaluacion__key UNIQUE (aca_grupo_asignatura_id, cat_tipo_evaluacion_id);


--
-- Name: aca_evaluacion aca_evaluacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_evaluacion
    ADD CONSTRAINT aca_evaluacion_pkey PRIMARY KEY (id);


--
-- Name: aca_grupo_asignatura aca_grupo_asignatura_aca_grupo_escolar_id_aca_asignatura_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_asignatura
    ADD CONSTRAINT aca_grupo_asignatura_aca_grupo_escolar_id_aca_asignatura_id_key UNIQUE (aca_grupo_escolar_id, aca_asignatura_id);


--
-- Name: aca_grupo_asignatura aca_grupo_asignatura_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_asignatura
    ADD CONSTRAINT aca_grupo_asignatura_pkey PRIMARY KEY (id);


--
-- Name: aca_grupo_escolar aca_grupo_escolar_aca_periodo_escolar_id_identificador_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_escolar
    ADD CONSTRAINT aca_grupo_escolar_aca_periodo_escolar_id_identificador_key UNIQUE (aca_periodo_escolar_id, identificador);


--
-- Name: aca_grupo_escolar aca_grupo_escolar_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_escolar
    ADD CONSTRAINT aca_grupo_escolar_pkey PRIMARY KEY (id);


--
-- Name: aca_horario aca_horario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_horario
    ADD CONSTRAINT aca_horario_pkey PRIMARY KEY (id);


--
-- Name: aca_inscripcion aca_inscripcion_per_alumno_id_aca_grupo_escolar_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_inscripcion
    ADD CONSTRAINT aca_inscripcion_per_alumno_id_aca_grupo_escolar_id_key UNIQUE (per_alumno_id, aca_grupo_escolar_id);


--
-- Name: aca_inscripcion aca_inscripcion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_inscripcion
    ADD CONSTRAINT aca_inscripcion_pkey PRIMARY KEY (id);


--
-- Name: aca_periodo_escolar aca_periodo_escolar_clave_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_periodo_escolar
    ADD CONSTRAINT aca_periodo_escolar_clave_key UNIQUE (clave);


--
-- Name: aca_periodo_escolar aca_periodo_escolar_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_periodo_escolar
    ADD CONSTRAINT aca_periodo_escolar_pkey PRIMARY KEY (id);


--
-- Name: aca_recursamiento aca_recursamiento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_recursamiento
    ADD CONSTRAINT aca_recursamiento_pkey PRIMARY KEY (id);


--
-- Name: aca_recursamiento aca_recursamiento_trx_resultado_asignatura_id_aca_grupo_asi_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_recursamiento
    ADD CONSTRAINT aca_recursamiento_trx_resultado_asignatura_id_aca_grupo_asi_key UNIQUE (trx_resultado_asignatura_id, aca_grupo_asignatura_id);


--
-- Name: aca_turno aca_turno_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_turno
    ADD CONSTRAINT aca_turno_nombre_key UNIQUE (nombre);


--
-- Name: aca_turno aca_turno_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_turno
    ADD CONSTRAINT aca_turno_pkey PRIMARY KEY (id);


--
-- Name: adm_actividad adm_actividad_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_actividad
    ADD CONSTRAINT adm_actividad_pkey PRIMARY KEY (id);


--
-- Name: adm_actividad_responsable adm_actividad_responsable_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_actividad_responsable
    ADD CONSTRAINT adm_actividad_responsable_pkey PRIMARY KEY (adm_actividad_id, user_id);


--
-- Name: adm_documento_normativo adm_documento_normativo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_documento_normativo
    ADD CONSTRAINT adm_documento_normativo_pkey PRIMARY KEY (id);


--
-- Name: adm_encuesta adm_encuesta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta
    ADD CONSTRAINT adm_encuesta_pkey PRIMARY KEY (id);


--
-- Name: adm_encuesta_pregunta adm_encuesta_pregunta_adm_encuesta_id_orden_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta_pregunta
    ADD CONSTRAINT adm_encuesta_pregunta_adm_encuesta_id_orden_key UNIQUE (adm_encuesta_id, orden);


--
-- Name: adm_encuesta_pregunta adm_encuesta_pregunta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta_pregunta
    ADD CONSTRAINT adm_encuesta_pregunta_pkey PRIMARY KEY (id);


--
-- Name: adm_encuesta_respuesta adm_encuesta_respuesta_adm_encuesta_pregunta_id_per_persona_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta_respuesta
    ADD CONSTRAINT adm_encuesta_respuesta_adm_encuesta_pregunta_id_per_persona_key UNIQUE (adm_encuesta_pregunta_id, per_persona_id);


--
-- Name: adm_encuesta_respuesta adm_encuesta_respuesta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta_respuesta
    ADD CONSTRAINT adm_encuesta_respuesta_pkey PRIMARY KEY (id);


--
-- Name: adm_entrega_producto adm_entrega_producto_adm_actividad_id_entregado_por_user_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_entrega_producto
    ADD CONSTRAINT adm_entrega_producto_adm_actividad_id_entregado_por_user_id_key UNIQUE (adm_actividad_id, entregado_por_user_id, version);


--
-- Name: adm_entrega_producto adm_entrega_producto_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_entrega_producto
    ADD CONSTRAINT adm_entrega_producto_pkey PRIMARY KEY (id);


--
-- Name: adm_historial_oficio adm_historial_oficio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_historial_oficio
    ADD CONSTRAINT adm_historial_oficio_pkey PRIMARY KEY (id);


--
-- Name: adm_oficio adm_oficio_org_area_origen_id_numero_oficio_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_oficio
    ADD CONSTRAINT adm_oficio_org_area_origen_id_numero_oficio_key UNIQUE (org_area_origen_id, numero_oficio);


--
-- Name: adm_oficio adm_oficio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_oficio
    ADD CONSTRAINT adm_oficio_pkey PRIMARY KEY (id);


--
-- Name: adm_procedimiento adm_procedimiento_clave_version_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_procedimiento
    ADD CONSTRAINT adm_procedimiento_clave_version_key UNIQUE (clave, version);


--
-- Name: adm_procedimiento adm_procedimiento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_procedimiento
    ADD CONSTRAINT adm_procedimiento_pkey PRIMARY KEY (id);


--
-- Name: adm_programa_avance adm_programa_avance_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_programa_avance
    ADD CONSTRAINT adm_programa_avance_pkey PRIMARY KEY (id);


--
-- Name: adm_programa_institucional adm_programa_institucional_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_programa_institucional
    ADD CONSTRAINT adm_programa_institucional_pkey PRIMARY KEY (id);


--
-- Name: archivo archivo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.archivo
    ADD CONSTRAINT archivo_pkey PRIMARY KEY (id);


--
-- Name: archivo archivo_ruta_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.archivo
    ADD CONSTRAINT archivo_ruta_key UNIQUE (ruta);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: cat_estado_alumno cat_estado_alumno_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_estado_alumno
    ADD CONSTRAINT cat_estado_alumno_nombre_key UNIQUE (nombre);


--
-- Name: cat_estado_alumno cat_estado_alumno_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_estado_alumno
    ADD CONSTRAINT cat_estado_alumno_pkey PRIMARY KEY (id);


--
-- Name: cat_estado_oficio cat_estado_oficio_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_estado_oficio
    ADD CONSTRAINT cat_estado_oficio_nombre_key UNIQUE (nombre);


--
-- Name: cat_estado_oficio cat_estado_oficio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_estado_oficio
    ADD CONSTRAINT cat_estado_oficio_pkey PRIMARY KEY (id);


--
-- Name: cat_medio_difusion cat_medio_difusion_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_medio_difusion
    ADD CONSTRAINT cat_medio_difusion_nombre_key UNIQUE (nombre);


--
-- Name: cat_medio_difusion cat_medio_difusion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_medio_difusion
    ADD CONSTRAINT cat_medio_difusion_pkey PRIMARY KEY (id);


--
-- Name: cat_tipo_asistencia cat_tipo_asistencia_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_tipo_asistencia
    ADD CONSTRAINT cat_tipo_asistencia_codigo_key UNIQUE (codigo);


--
-- Name: cat_tipo_asistencia cat_tipo_asistencia_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_tipo_asistencia
    ADD CONSTRAINT cat_tipo_asistencia_pkey PRIMARY KEY (id);


--
-- Name: cat_tipo_evaluacion cat_tipo_evaluacion_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_tipo_evaluacion
    ADD CONSTRAINT cat_tipo_evaluacion_nombre_key UNIQUE (nombre);


--
-- Name: cat_tipo_evaluacion cat_tipo_evaluacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_tipo_evaluacion
    ADD CONSTRAINT cat_tipo_evaluacion_pkey PRIMARY KEY (id);


--
-- Name: cat_tipo_incidencia cat_tipo_incidencia_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_tipo_incidencia
    ADD CONSTRAINT cat_tipo_incidencia_nombre_key UNIQUE (nombre);


--
-- Name: cat_tipo_incidencia cat_tipo_incidencia_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cat_tipo_incidencia
    ADD CONSTRAINT cat_tipo_incidencia_pkey PRIMARY KEY (id);


--
-- Name: com_notificacion_intento com_notificacion_intento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.com_notificacion_intento
    ADD CONSTRAINT com_notificacion_intento_pkey PRIMARY KEY (id);


--
-- Name: com_notificacion com_notificacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.com_notificacion
    ADD CONSTRAINT com_notificacion_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_key UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: org_area org_area_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_area
    ADD CONSTRAINT org_area_nombre_key UNIQUE (nombre);


--
-- Name: org_area org_area_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_area
    ADD CONSTRAINT org_area_pkey PRIMARY KEY (id);


--
-- Name: org_convenio org_convenio_numero_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_convenio
    ADD CONSTRAINT org_convenio_numero_key UNIQUE (numero);


--
-- Name: org_convenio org_convenio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_convenio
    ADD CONSTRAINT org_convenio_pkey PRIMARY KEY (id);


--
-- Name: org_empresa org_empresa_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_empresa
    ADD CONSTRAINT org_empresa_pkey PRIMARY KEY (id);


--
-- Name: org_empresa org_empresa_rfc_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_empresa
    ADD CONSTRAINT org_empresa_rfc_key UNIQUE (rfc);


--
-- Name: org_espacio_fisico org_espacio_fisico_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_espacio_fisico
    ADD CONSTRAINT org_espacio_fisico_nombre_key UNIQUE (nombre);


--
-- Name: org_espacio_fisico org_espacio_fisico_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_espacio_fisico
    ADD CONSTRAINT org_espacio_fisico_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: per_alumno per_alumno_email_institucional_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno
    ADD CONSTRAINT per_alumno_email_institucional_key UNIQUE (email_institucional);


--
-- Name: per_alumno per_alumno_matricula_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno
    ADD CONSTRAINT per_alumno_matricula_key UNIQUE (matricula);


--
-- Name: per_alumno per_alumno_per_persona_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno
    ADD CONSTRAINT per_alumno_per_persona_id_key UNIQUE (per_persona_id);


--
-- Name: per_alumno per_alumno_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno
    ADD CONSTRAINT per_alumno_pkey PRIMARY KEY (id);


--
-- Name: per_alumno_salud per_alumno_salud_nss_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno_salud
    ADD CONSTRAINT per_alumno_salud_nss_key UNIQUE (nss);


--
-- Name: per_alumno_salud per_alumno_salud_per_alumno_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno_salud
    ADD CONSTRAINT per_alumno_salud_per_alumno_id_key UNIQUE (per_alumno_id);


--
-- Name: per_alumno_salud per_alumno_salud_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno_salud
    ADD CONSTRAINT per_alumno_salud_pkey PRIMARY KEY (id);


--
-- Name: per_consentimiento per_consentimiento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_consentimiento
    ADD CONSTRAINT per_consentimiento_pkey PRIMARY KEY (id);


--
-- Name: per_credencial_acceso per_credencial_acceso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_credencial_acceso
    ADD CONSTRAINT per_credencial_acceso_pkey PRIMARY KEY (id);


--
-- Name: per_credencial_acceso per_credencial_acceso_token_hash_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_credencial_acceso
    ADD CONSTRAINT per_credencial_acceso_token_hash_key UNIQUE (token_hash);


--
-- Name: per_persona per_persona_curp_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_persona
    ADD CONSTRAINT per_persona_curp_key UNIQUE (curp);


--
-- Name: per_persona per_persona_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_persona
    ADD CONSTRAINT per_persona_pkey PRIMARY KEY (id);


--
-- Name: per_personal per_personal_numero_empleado_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_personal
    ADD CONSTRAINT per_personal_numero_empleado_key UNIQUE (numero_empleado);


--
-- Name: per_personal per_personal_per_persona_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_personal
    ADD CONSTRAINT per_personal_per_persona_id_key UNIQUE (per_persona_id);


--
-- Name: per_personal per_personal_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_personal
    ADD CONSTRAINT per_personal_pkey PRIMARY KEY (id);


--
-- Name: per_tutor_alumno per_tutor_alumno_per_alumno_id_per_persona_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_tutor_alumno
    ADD CONSTRAINT per_tutor_alumno_per_alumno_id_per_persona_id_key UNIQUE (per_alumno_id, per_persona_id);


--
-- Name: per_tutor_alumno per_tutor_alumno_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_tutor_alumno
    ADD CONSTRAINT per_tutor_alumno_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_name_guard_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_key UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_key UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: sys_auditoria_detalle sys_auditoria_detalle_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sys_auditoria_detalle
    ADD CONSTRAINT sys_auditoria_detalle_pkey PRIMARY KEY (id);


--
-- Name: sys_auditoria sys_auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sys_auditoria
    ADD CONSTRAINT sys_auditoria_pkey PRIMARY KEY (id);


--
-- Name: sys_configuracion sys_configuracion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sys_configuracion
    ADD CONSTRAINT sys_configuracion_pkey PRIMARY KEY (id);


--
-- Name: trx_asistencia_clase trx_asistencia_clase_aca_inscripcion_id_aca_grupo_asignatur_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_clase
    ADD CONSTRAINT trx_asistencia_clase_aca_inscripcion_id_aca_grupo_asignatur_key UNIQUE (aca_inscripcion_id, aca_grupo_asignatura_id, fecha);


--
-- Name: trx_asistencia_clase trx_asistencia_clase_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_clase
    ADD CONSTRAINT trx_asistencia_clase_pkey PRIMARY KEY (id);


--
-- Name: trx_asistencia_diaria trx_asistencia_diaria_aca_inscripcion_id_fecha_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_diaria
    ADD CONSTRAINT trx_asistencia_diaria_aca_inscripcion_id_fecha_key UNIQUE (aca_inscripcion_id, fecha);


--
-- Name: trx_asistencia_diaria trx_asistencia_diaria_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_diaria
    ADD CONSTRAINT trx_asistencia_diaria_pkey PRIMARY KEY (id);


--
-- Name: trx_aspirante trx_aspirante_per_persona_id_ciclo_ingreso_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_aspirante
    ADD CONSTRAINT trx_aspirante_per_persona_id_ciclo_ingreso_key UNIQUE (per_persona_id, ciclo_ingreso);


--
-- Name: trx_aspirante trx_aspirante_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_aspirante
    ADD CONSTRAINT trx_aspirante_pkey PRIMARY KEY (id);


--
-- Name: trx_calificacion trx_calificacion_aca_inscripcion_id_aca_evaluacion_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_calificacion
    ADD CONSTRAINT trx_calificacion_aca_inscripcion_id_aca_evaluacion_id_key UNIQUE (aca_inscripcion_id, aca_evaluacion_id);


--
-- Name: trx_calificacion trx_calificacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_calificacion
    ADD CONSTRAINT trx_calificacion_pkey PRIMARY KEY (id);


--
-- Name: trx_egreso trx_egreso_per_alumno_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_egreso
    ADD CONSTRAINT trx_egreso_per_alumno_id_key UNIQUE (per_alumno_id);


--
-- Name: trx_egreso trx_egreso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_egreso
    ADD CONSTRAINT trx_egreso_pkey PRIMARY KEY (id);


--
-- Name: trx_incidencia trx_incidencia_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_incidencia
    ADD CONSTRAINT trx_incidencia_pkey PRIMARY KEY (id);


--
-- Name: trx_permiso_confirmacion trx_permiso_confirmacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_permiso_confirmacion
    ADD CONSTRAINT trx_permiso_confirmacion_pkey PRIMARY KEY (id);


--
-- Name: trx_permiso_salida trx_permiso_salida_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_permiso_salida
    ADD CONSTRAINT trx_permiso_salida_pkey PRIMARY KEY (id);


--
-- Name: trx_proceso_egreso trx_proceso_egreso_per_alumno_id_tipo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_proceso_egreso
    ADD CONSTRAINT trx_proceso_egreso_per_alumno_id_tipo_key UNIQUE (per_alumno_id, tipo);


--
-- Name: trx_proceso_egreso trx_proceso_egreso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_proceso_egreso
    ADD CONSTRAINT trx_proceso_egreso_pkey PRIMARY KEY (id);


--
-- Name: trx_programa_dual trx_programa_dual_aca_inscripcion_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_programa_dual
    ADD CONSTRAINT trx_programa_dual_aca_inscripcion_id_key UNIQUE (aca_inscripcion_id);


--
-- Name: trx_programa_dual trx_programa_dual_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_programa_dual
    ADD CONSTRAINT trx_programa_dual_pkey PRIMARY KEY (id);


--
-- Name: trx_registro_acceso trx_registro_acceso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_registro_acceso
    ADD CONSTRAINT trx_registro_acceso_pkey PRIMARY KEY (id);


--
-- Name: trx_reporte_dual trx_reporte_dual_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_reporte_dual
    ADD CONSTRAINT trx_reporte_dual_pkey PRIMARY KEY (id);


--
-- Name: trx_reporte_dual trx_reporte_dual_trx_programa_dual_id_numero_reporte_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_reporte_dual
    ADD CONSTRAINT trx_reporte_dual_trx_programa_dual_id_numero_reporte_key UNIQUE (trx_programa_dual_id, numero_reporte);


--
-- Name: trx_resultado_asignatura trx_resultado_asignatura_aca_inscripcion_id_aca_grupo_asign_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_resultado_asignatura
    ADD CONSTRAINT trx_resultado_asignatura_aca_inscripcion_id_aca_grupo_asign_key UNIQUE (aca_inscripcion_id, aca_grupo_asignatura_id);


--
-- Name: trx_resultado_asignatura trx_resultado_asignatura_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_resultado_asignatura
    ADD CONSTRAINT trx_resultado_asignatura_pkey PRIMARY KEY (id);


--
-- Name: trx_seguimiento_alumno trx_seguimiento_alumno_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_alumno
    ADD CONSTRAINT trx_seguimiento_alumno_pkey PRIMARY KEY (id);


--
-- Name: trx_seguimiento_egresado trx_seguimiento_egresado_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_egresado
    ADD CONSTRAINT trx_seguimiento_egresado_pkey PRIMARY KEY (id);


--
-- Name: trx_seguimiento_nota trx_seguimiento_nota_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_nota
    ADD CONSTRAINT trx_seguimiento_nota_pkey PRIMARY KEY (id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_per_persona_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_per_persona_id_key UNIQUE (per_persona_id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: sys_auditoria_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sys_auditoria_created_at_index ON public.sys_auditoria USING btree (created_at);


--
-- Name: sys_auditoria_detalle_sys_auditoria_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sys_auditoria_detalle_sys_auditoria_id_index ON public.sys_auditoria_detalle USING btree (sys_auditoria_id);


--
-- Name: sys_auditoria_tabla_registro_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sys_auditoria_tabla_registro_index ON public.sys_auditoria USING btree (tabla_afectada, registro_id);


--
-- Name: sys_auditoria_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sys_auditoria_user_id_index ON public.sys_auditoria USING btree (user_id);


--
-- Name: aca_baja aca_baja_aca_inscripcion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_baja
    ADD CONSTRAINT aca_baja_aca_inscripcion_id_fkey FOREIGN KEY (aca_inscripcion_id) REFERENCES public.aca_inscripcion(id);


--
-- Name: aca_baja aca_baja_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_baja
    ADD CONSTRAINT aca_baja_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: aca_evaluacion aca_evaluacion_aca_grupo_asignatura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_evaluacion
    ADD CONSTRAINT aca_evaluacion_aca_grupo_asignatura_id_fkey FOREIGN KEY (aca_grupo_asignatura_id) REFERENCES public.aca_grupo_asignatura(id);


--
-- Name: aca_evaluacion aca_evaluacion_cat_tipo_evaluacion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_evaluacion
    ADD CONSTRAINT aca_evaluacion_cat_tipo_evaluacion_id_fkey FOREIGN KEY (cat_tipo_evaluacion_id) REFERENCES public.cat_tipo_evaluacion(id);


--
-- Name: aca_grupo_asignatura aca_grupo_asignatura_aca_asignatura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_asignatura
    ADD CONSTRAINT aca_grupo_asignatura_aca_asignatura_id_fkey FOREIGN KEY (aca_asignatura_id) REFERENCES public.aca_asignatura(id);


--
-- Name: aca_grupo_asignatura aca_grupo_asignatura_aca_grupo_escolar_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_asignatura
    ADD CONSTRAINT aca_grupo_asignatura_aca_grupo_escolar_id_fkey FOREIGN KEY (aca_grupo_escolar_id) REFERENCES public.aca_grupo_escolar(id);


--
-- Name: aca_grupo_asignatura aca_grupo_asignatura_docente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_asignatura
    ADD CONSTRAINT aca_grupo_asignatura_docente_id_fkey FOREIGN KEY (docente_id) REFERENCES public.per_personal(id);


--
-- Name: aca_grupo_escolar aca_grupo_escolar_aca_carrera_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_escolar
    ADD CONSTRAINT aca_grupo_escolar_aca_carrera_id_fkey FOREIGN KEY (aca_carrera_id) REFERENCES public.aca_carrera(id);


--
-- Name: aca_grupo_escolar aca_grupo_escolar_aca_periodo_escolar_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_escolar
    ADD CONSTRAINT aca_grupo_escolar_aca_periodo_escolar_id_fkey FOREIGN KEY (aca_periodo_escolar_id) REFERENCES public.aca_periodo_escolar(id);


--
-- Name: aca_grupo_escolar aca_grupo_escolar_aca_turno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_escolar
    ADD CONSTRAINT aca_grupo_escolar_aca_turno_id_fkey FOREIGN KEY (aca_turno_id) REFERENCES public.aca_turno(id);


--
-- Name: aca_grupo_escolar aca_grupo_escolar_orientador_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_escolar
    ADD CONSTRAINT aca_grupo_escolar_orientador_id_fkey FOREIGN KEY (orientador_id) REFERENCES public.per_personal(id);


--
-- Name: aca_grupo_escolar aca_grupo_escolar_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_grupo_escolar
    ADD CONSTRAINT aca_grupo_escolar_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES public.per_personal(id);


--
-- Name: aca_horario aca_horario_aca_grupo_asignatura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_horario
    ADD CONSTRAINT aca_horario_aca_grupo_asignatura_id_fkey FOREIGN KEY (aca_grupo_asignatura_id) REFERENCES public.aca_grupo_asignatura(id);


--
-- Name: aca_horario aca_horario_org_espacio_fisico_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_horario
    ADD CONSTRAINT aca_horario_org_espacio_fisico_id_fkey FOREIGN KEY (org_espacio_fisico_id) REFERENCES public.org_espacio_fisico(id);


--
-- Name: aca_inscripcion aca_inscripcion_aca_grupo_escolar_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_inscripcion
    ADD CONSTRAINT aca_inscripcion_aca_grupo_escolar_id_fkey FOREIGN KEY (aca_grupo_escolar_id) REFERENCES public.aca_grupo_escolar(id);


--
-- Name: aca_inscripcion aca_inscripcion_per_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_inscripcion
    ADD CONSTRAINT aca_inscripcion_per_alumno_id_fkey FOREIGN KEY (per_alumno_id) REFERENCES public.per_alumno(id);


--
-- Name: aca_recursamiento aca_recursamiento_aca_grupo_asignatura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_recursamiento
    ADD CONSTRAINT aca_recursamiento_aca_grupo_asignatura_id_fkey FOREIGN KEY (aca_grupo_asignatura_id) REFERENCES public.aca_grupo_asignatura(id);


--
-- Name: aca_recursamiento aca_recursamiento_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_recursamiento
    ADD CONSTRAINT aca_recursamiento_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: aca_recursamiento aca_recursamiento_trx_resultado_asignatura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.aca_recursamiento
    ADD CONSTRAINT aca_recursamiento_trx_resultado_asignatura_id_fkey FOREIGN KEY (trx_resultado_asignatura_id) REFERENCES public.trx_resultado_asignatura(id);


--
-- Name: adm_actividad adm_actividad_creado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_actividad
    ADD CONSTRAINT adm_actividad_creado_por_user_id_fkey FOREIGN KEY (creado_por_user_id) REFERENCES public.users(id);


--
-- Name: adm_actividad adm_actividad_org_area_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_actividad
    ADD CONSTRAINT adm_actividad_org_area_id_fkey FOREIGN KEY (org_area_id) REFERENCES public.org_area(id);


--
-- Name: adm_actividad_responsable adm_actividad_responsable_adm_actividad_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_actividad_responsable
    ADD CONSTRAINT adm_actividad_responsable_adm_actividad_id_fkey FOREIGN KEY (adm_actividad_id) REFERENCES public.adm_actividad(id) ON DELETE CASCADE;


--
-- Name: adm_actividad_responsable adm_actividad_responsable_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_actividad_responsable
    ADD CONSTRAINT adm_actividad_responsable_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: adm_documento_normativo adm_documento_normativo_archivo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_documento_normativo
    ADD CONSTRAINT adm_documento_normativo_archivo_id_fkey FOREIGN KEY (archivo_id) REFERENCES public.archivo(id);


--
-- Name: adm_encuesta adm_encuesta_creado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta
    ADD CONSTRAINT adm_encuesta_creado_por_user_id_fkey FOREIGN KEY (creado_por_user_id) REFERENCES public.users(id);


--
-- Name: adm_encuesta_pregunta adm_encuesta_pregunta_adm_encuesta_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta_pregunta
    ADD CONSTRAINT adm_encuesta_pregunta_adm_encuesta_id_fkey FOREIGN KEY (adm_encuesta_id) REFERENCES public.adm_encuesta(id) ON DELETE CASCADE;


--
-- Name: adm_encuesta_respuesta adm_encuesta_respuesta_adm_encuesta_pregunta_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta_respuesta
    ADD CONSTRAINT adm_encuesta_respuesta_adm_encuesta_pregunta_id_fkey FOREIGN KEY (adm_encuesta_pregunta_id) REFERENCES public.adm_encuesta_pregunta(id) ON DELETE CASCADE;


--
-- Name: adm_encuesta_respuesta adm_encuesta_respuesta_per_persona_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_encuesta_respuesta
    ADD CONSTRAINT adm_encuesta_respuesta_per_persona_id_fkey FOREIGN KEY (per_persona_id) REFERENCES public.per_persona(id);


--
-- Name: adm_entrega_producto adm_entrega_producto_adm_actividad_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_entrega_producto
    ADD CONSTRAINT adm_entrega_producto_adm_actividad_id_fkey FOREIGN KEY (adm_actividad_id) REFERENCES public.adm_actividad(id);


--
-- Name: adm_entrega_producto adm_entrega_producto_archivo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_entrega_producto
    ADD CONSTRAINT adm_entrega_producto_archivo_id_fkey FOREIGN KEY (archivo_id) REFERENCES public.archivo(id);


--
-- Name: adm_entrega_producto adm_entrega_producto_entregado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_entrega_producto
    ADD CONSTRAINT adm_entrega_producto_entregado_por_user_id_fkey FOREIGN KEY (entregado_por_user_id) REFERENCES public.users(id);


--
-- Name: adm_historial_oficio adm_historial_oficio_adm_oficio_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_historial_oficio
    ADD CONSTRAINT adm_historial_oficio_adm_oficio_id_fkey FOREIGN KEY (adm_oficio_id) REFERENCES public.adm_oficio(id);


--
-- Name: adm_historial_oficio adm_historial_oficio_cat_estado_oficio_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_historial_oficio
    ADD CONSTRAINT adm_historial_oficio_cat_estado_oficio_id_fkey FOREIGN KEY (cat_estado_oficio_id) REFERENCES public.cat_estado_oficio(id);


--
-- Name: adm_historial_oficio adm_historial_oficio_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_historial_oficio
    ADD CONSTRAINT adm_historial_oficio_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: adm_oficio adm_oficio_archivo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_oficio
    ADD CONSTRAINT adm_oficio_archivo_id_fkey FOREIGN KEY (archivo_id) REFERENCES public.archivo(id);


--
-- Name: adm_oficio adm_oficio_cat_estado_oficio_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_oficio
    ADD CONSTRAINT adm_oficio_cat_estado_oficio_id_fkey FOREIGN KEY (cat_estado_oficio_id) REFERENCES public.cat_estado_oficio(id);


--
-- Name: adm_oficio adm_oficio_org_area_destino_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_oficio
    ADD CONSTRAINT adm_oficio_org_area_destino_id_fkey FOREIGN KEY (org_area_destino_id) REFERENCES public.org_area(id);


--
-- Name: adm_oficio adm_oficio_org_area_origen_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_oficio
    ADD CONSTRAINT adm_oficio_org_area_origen_id_fkey FOREIGN KEY (org_area_origen_id) REFERENCES public.org_area(id);


--
-- Name: adm_oficio adm_oficio_responsable_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_oficio
    ADD CONSTRAINT adm_oficio_responsable_user_id_fkey FOREIGN KEY (responsable_user_id) REFERENCES public.users(id);


--
-- Name: adm_procedimiento adm_procedimiento_org_area_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_procedimiento
    ADD CONSTRAINT adm_procedimiento_org_area_id_fkey FOREIGN KEY (org_area_id) REFERENCES public.org_area(id);


--
-- Name: adm_programa_avance adm_programa_avance_adm_programa_institucional_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_programa_avance
    ADD CONSTRAINT adm_programa_avance_adm_programa_institucional_id_fkey FOREIGN KEY (adm_programa_institucional_id) REFERENCES public.adm_programa_institucional(id);


--
-- Name: adm_programa_avance adm_programa_avance_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_programa_avance
    ADD CONSTRAINT adm_programa_avance_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: adm_programa_institucional adm_programa_institucional_aca_periodo_escolar_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adm_programa_institucional
    ADD CONSTRAINT adm_programa_institucional_aca_periodo_escolar_id_fkey FOREIGN KEY (aca_periodo_escolar_id) REFERENCES public.aca_periodo_escolar(id);


--
-- Name: archivo archivo_subido_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.archivo
    ADD CONSTRAINT archivo_subido_por_user_id_fkey FOREIGN KEY (subido_por_user_id) REFERENCES public.users(id);


--
-- Name: com_notificacion_intento com_notificacion_intento_com_notificacion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.com_notificacion_intento
    ADD CONSTRAINT com_notificacion_intento_com_notificacion_id_fkey FOREIGN KEY (com_notificacion_id) REFERENCES public.com_notificacion(id) ON DELETE CASCADE;


--
-- Name: com_notificacion com_notificacion_per_persona_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.com_notificacion
    ADD CONSTRAINT com_notificacion_per_persona_id_fkey FOREIGN KEY (per_persona_id) REFERENCES public.per_persona(id);


--
-- Name: model_has_permissions model_has_permissions_permission_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: org_convenio org_convenio_archivo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_convenio
    ADD CONSTRAINT org_convenio_archivo_id_fkey FOREIGN KEY (archivo_id) REFERENCES public.archivo(id);


--
-- Name: org_convenio org_convenio_org_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.org_convenio
    ADD CONSTRAINT org_convenio_org_empresa_id_fkey FOREIGN KEY (org_empresa_id) REFERENCES public.org_empresa(id);


--
-- Name: per_alumno per_alumno_aca_carrera_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno
    ADD CONSTRAINT per_alumno_aca_carrera_id_fkey FOREIGN KEY (aca_carrera_id) REFERENCES public.aca_carrera(id);


--
-- Name: per_alumno per_alumno_cat_estado_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno
    ADD CONSTRAINT per_alumno_cat_estado_alumno_id_fkey FOREIGN KEY (cat_estado_alumno_id) REFERENCES public.cat_estado_alumno(id);


--
-- Name: per_alumno per_alumno_expediente_archivo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno
    ADD CONSTRAINT per_alumno_expediente_archivo_id_fkey FOREIGN KEY (expediente_archivo_id) REFERENCES public.archivo(id);


--
-- Name: per_alumno per_alumno_per_persona_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno
    ADD CONSTRAINT per_alumno_per_persona_id_fkey FOREIGN KEY (per_persona_id) REFERENCES public.per_persona(id);


--
-- Name: per_alumno_salud per_alumno_salud_per_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_alumno_salud
    ADD CONSTRAINT per_alumno_salud_per_alumno_id_fkey FOREIGN KEY (per_alumno_id) REFERENCES public.per_alumno(id) ON DELETE CASCADE;


--
-- Name: per_consentimiento per_consentimiento_archivo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_consentimiento
    ADD CONSTRAINT per_consentimiento_archivo_id_fkey FOREIGN KEY (archivo_id) REFERENCES public.archivo(id);


--
-- Name: per_consentimiento per_consentimiento_per_tutor_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_consentimiento
    ADD CONSTRAINT per_consentimiento_per_tutor_alumno_id_fkey FOREIGN KEY (per_tutor_alumno_id) REFERENCES public.per_tutor_alumno(id);


--
-- Name: per_credencial_acceso per_credencial_acceso_per_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_credencial_acceso
    ADD CONSTRAINT per_credencial_acceso_per_alumno_id_fkey FOREIGN KEY (per_alumno_id) REFERENCES public.per_alumno(id);


--
-- Name: per_personal per_personal_org_area_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_personal
    ADD CONSTRAINT per_personal_org_area_id_fkey FOREIGN KEY (org_area_id) REFERENCES public.org_area(id);


--
-- Name: per_personal per_personal_per_persona_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_personal
    ADD CONSTRAINT per_personal_per_persona_id_fkey FOREIGN KEY (per_persona_id) REFERENCES public.per_persona(id);


--
-- Name: per_tutor_alumno per_tutor_alumno_per_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_tutor_alumno
    ADD CONSTRAINT per_tutor_alumno_per_alumno_id_fkey FOREIGN KEY (per_alumno_id) REFERENCES public.per_alumno(id) ON DELETE CASCADE;


--
-- Name: per_tutor_alumno per_tutor_alumno_per_persona_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.per_tutor_alumno
    ADD CONSTRAINT per_tutor_alumno_per_persona_id_fkey FOREIGN KEY (per_persona_id) REFERENCES public.per_persona(id);


--
-- Name: role_has_permissions role_has_permissions_permission_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: sys_auditoria_detalle sys_auditoria_detalle_sys_auditoria_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sys_auditoria_detalle
    ADD CONSTRAINT sys_auditoria_detalle_sys_auditoria_id_fkey FOREIGN KEY (sys_auditoria_id) REFERENCES public.sys_auditoria(id);


--
-- Name: trx_asistencia_clase trx_asistencia_clase_aca_grupo_asignatura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_clase
    ADD CONSTRAINT trx_asistencia_clase_aca_grupo_asignatura_id_fkey FOREIGN KEY (aca_grupo_asignatura_id) REFERENCES public.aca_grupo_asignatura(id);


--
-- Name: trx_asistencia_clase trx_asistencia_clase_aca_inscripcion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_clase
    ADD CONSTRAINT trx_asistencia_clase_aca_inscripcion_id_fkey FOREIGN KEY (aca_inscripcion_id) REFERENCES public.aca_inscripcion(id);


--
-- Name: trx_asistencia_clase trx_asistencia_clase_cat_tipo_asistencia_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_clase
    ADD CONSTRAINT trx_asistencia_clase_cat_tipo_asistencia_id_fkey FOREIGN KEY (cat_tipo_asistencia_id) REFERENCES public.cat_tipo_asistencia(id);


--
-- Name: trx_asistencia_clase trx_asistencia_clase_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_clase
    ADD CONSTRAINT trx_asistencia_clase_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_asistencia_diaria trx_asistencia_diaria_aca_inscripcion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_asistencia_diaria
    ADD CONSTRAINT trx_asistencia_diaria_aca_inscripcion_id_fkey FOREIGN KEY (aca_inscripcion_id) REFERENCES public.aca_inscripcion(id);


--
-- Name: trx_aspirante trx_aspirante_aca_carrera_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_aspirante
    ADD CONSTRAINT trx_aspirante_aca_carrera_id_fkey FOREIGN KEY (aca_carrera_id) REFERENCES public.aca_carrera(id);


--
-- Name: trx_aspirante trx_aspirante_cat_medio_difusion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_aspirante
    ADD CONSTRAINT trx_aspirante_cat_medio_difusion_id_fkey FOREIGN KEY (cat_medio_difusion_id) REFERENCES public.cat_medio_difusion(id);


--
-- Name: trx_aspirante trx_aspirante_per_persona_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_aspirante
    ADD CONSTRAINT trx_aspirante_per_persona_id_fkey FOREIGN KEY (per_persona_id) REFERENCES public.per_persona(id);


--
-- Name: trx_calificacion trx_calificacion_aca_evaluacion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_calificacion
    ADD CONSTRAINT trx_calificacion_aca_evaluacion_id_fkey FOREIGN KEY (aca_evaluacion_id) REFERENCES public.aca_evaluacion(id);


--
-- Name: trx_calificacion trx_calificacion_aca_inscripcion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_calificacion
    ADD CONSTRAINT trx_calificacion_aca_inscripcion_id_fkey FOREIGN KEY (aca_inscripcion_id) REFERENCES public.aca_inscripcion(id);


--
-- Name: trx_calificacion trx_calificacion_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_calificacion
    ADD CONSTRAINT trx_calificacion_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_egreso trx_egreso_per_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_egreso
    ADD CONSTRAINT trx_egreso_per_alumno_id_fkey FOREIGN KEY (per_alumno_id) REFERENCES public.per_alumno(id);


--
-- Name: trx_incidencia trx_incidencia_aca_inscripcion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_incidencia
    ADD CONSTRAINT trx_incidencia_aca_inscripcion_id_fkey FOREIGN KEY (aca_inscripcion_id) REFERENCES public.aca_inscripcion(id);


--
-- Name: trx_incidencia trx_incidencia_archivo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_incidencia
    ADD CONSTRAINT trx_incidencia_archivo_id_fkey FOREIGN KEY (archivo_id) REFERENCES public.archivo(id);


--
-- Name: trx_incidencia trx_incidencia_cat_tipo_incidencia_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_incidencia
    ADD CONSTRAINT trx_incidencia_cat_tipo_incidencia_id_fkey FOREIGN KEY (cat_tipo_incidencia_id) REFERENCES public.cat_tipo_incidencia(id);


--
-- Name: trx_incidencia trx_incidencia_per_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_incidencia
    ADD CONSTRAINT trx_incidencia_per_alumno_id_fkey FOREIGN KEY (per_alumno_id) REFERENCES public.per_alumno(id);


--
-- Name: trx_incidencia trx_incidencia_reportado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_incidencia
    ADD CONSTRAINT trx_incidencia_reportado_por_user_id_fkey FOREIGN KEY (reportado_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_incidencia trx_incidencia_resuelta_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_incidencia
    ADD CONSTRAINT trx_incidencia_resuelta_por_user_id_fkey FOREIGN KEY (resuelta_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_permiso_confirmacion trx_permiso_confirmacion_per_tutor_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_permiso_confirmacion
    ADD CONSTRAINT trx_permiso_confirmacion_per_tutor_alumno_id_fkey FOREIGN KEY (per_tutor_alumno_id) REFERENCES public.per_tutor_alumno(id);


--
-- Name: trx_permiso_confirmacion trx_permiso_confirmacion_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_permiso_confirmacion
    ADD CONSTRAINT trx_permiso_confirmacion_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_permiso_confirmacion trx_permiso_confirmacion_trx_permiso_salida_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_permiso_confirmacion
    ADD CONSTRAINT trx_permiso_confirmacion_trx_permiso_salida_id_fkey FOREIGN KEY (trx_permiso_salida_id) REFERENCES public.trx_permiso_salida(id);


--
-- Name: trx_permiso_salida trx_permiso_salida_aca_inscripcion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_permiso_salida
    ADD CONSTRAINT trx_permiso_salida_aca_inscripcion_id_fkey FOREIGN KEY (aca_inscripcion_id) REFERENCES public.aca_inscripcion(id);


--
-- Name: trx_permiso_salida trx_permiso_salida_solicitado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_permiso_salida
    ADD CONSTRAINT trx_permiso_salida_solicitado_por_user_id_fkey FOREIGN KEY (solicitado_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_proceso_egreso trx_proceso_egreso_org_convenio_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_proceso_egreso
    ADD CONSTRAINT trx_proceso_egreso_org_convenio_id_fkey FOREIGN KEY (org_convenio_id) REFERENCES public.org_convenio(id);


--
-- Name: trx_proceso_egreso trx_proceso_egreso_per_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_proceso_egreso
    ADD CONSTRAINT trx_proceso_egreso_per_alumno_id_fkey FOREIGN KEY (per_alumno_id) REFERENCES public.per_alumno(id);


--
-- Name: trx_programa_dual trx_programa_dual_aca_inscripcion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_programa_dual
    ADD CONSTRAINT trx_programa_dual_aca_inscripcion_id_fkey FOREIGN KEY (aca_inscripcion_id) REFERENCES public.aca_inscripcion(id);


--
-- Name: trx_programa_dual trx_programa_dual_org_convenio_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_programa_dual
    ADD CONSTRAINT trx_programa_dual_org_convenio_id_fkey FOREIGN KEY (org_convenio_id) REFERENCES public.org_convenio(id);


--
-- Name: trx_programa_dual trx_programa_dual_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_programa_dual
    ADD CONSTRAINT trx_programa_dual_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES public.per_personal(id);


--
-- Name: trx_registro_acceso trx_registro_acceso_per_credencial_acceso_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_registro_acceso
    ADD CONSTRAINT trx_registro_acceso_per_credencial_acceso_id_fkey FOREIGN KEY (per_credencial_acceso_id) REFERENCES public.per_credencial_acceso(id);


--
-- Name: trx_registro_acceso trx_registro_acceso_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_registro_acceso
    ADD CONSTRAINT trx_registro_acceso_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_reporte_dual trx_reporte_dual_archivo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_reporte_dual
    ADD CONSTRAINT trx_reporte_dual_archivo_id_fkey FOREIGN KEY (archivo_id) REFERENCES public.archivo(id);


--
-- Name: trx_reporte_dual trx_reporte_dual_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_reporte_dual
    ADD CONSTRAINT trx_reporte_dual_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_reporte_dual trx_reporte_dual_trx_programa_dual_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_reporte_dual
    ADD CONSTRAINT trx_reporte_dual_trx_programa_dual_id_fkey FOREIGN KEY (trx_programa_dual_id) REFERENCES public.trx_programa_dual(id);


--
-- Name: trx_resultado_asignatura trx_resultado_asignatura_aca_grupo_asignatura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_resultado_asignatura
    ADD CONSTRAINT trx_resultado_asignatura_aca_grupo_asignatura_id_fkey FOREIGN KEY (aca_grupo_asignatura_id) REFERENCES public.aca_grupo_asignatura(id);


--
-- Name: trx_resultado_asignatura trx_resultado_asignatura_aca_inscripcion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_resultado_asignatura
    ADD CONSTRAINT trx_resultado_asignatura_aca_inscripcion_id_fkey FOREIGN KEY (aca_inscripcion_id) REFERENCES public.aca_inscripcion(id);


--
-- Name: trx_resultado_asignatura trx_resultado_asignatura_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_resultado_asignatura
    ADD CONSTRAINT trx_resultado_asignatura_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_seguimiento_alumno trx_seguimiento_alumno_abierto_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_alumno
    ADD CONSTRAINT trx_seguimiento_alumno_abierto_por_user_id_fkey FOREIGN KEY (abierto_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_seguimiento_alumno trx_seguimiento_alumno_aca_inscripcion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_alumno
    ADD CONSTRAINT trx_seguimiento_alumno_aca_inscripcion_id_fkey FOREIGN KEY (aca_inscripcion_id) REFERENCES public.aca_inscripcion(id);


--
-- Name: trx_seguimiento_egresado trx_seguimiento_egresado_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_egresado
    ADD CONSTRAINT trx_seguimiento_egresado_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_seguimiento_egresado trx_seguimiento_egresado_trx_egreso_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_egresado
    ADD CONSTRAINT trx_seguimiento_egresado_trx_egreso_id_fkey FOREIGN KEY (trx_egreso_id) REFERENCES public.trx_egreso(id);


--
-- Name: trx_seguimiento_nota trx_seguimiento_nota_registrado_por_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_nota
    ADD CONSTRAINT trx_seguimiento_nota_registrado_por_user_id_fkey FOREIGN KEY (registrado_por_user_id) REFERENCES public.users(id);


--
-- Name: trx_seguimiento_nota trx_seguimiento_nota_trx_seguimiento_alumno_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trx_seguimiento_nota
    ADD CONSTRAINT trx_seguimiento_nota_trx_seguimiento_alumno_id_fkey FOREIGN KEY (trx_seguimiento_alumno_id) REFERENCES public.trx_seguimiento_alumno(id);


--
-- Name: users users_per_persona_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_per_persona_id_fkey FOREIGN KEY (per_persona_id) REFERENCES public.per_persona(id);


--
-- PostgreSQL database dump complete
--

\unrestrict XcO7fw9ldXiLbMn9lpavhfnqAptURRy5cJbYKKFWyq1J9AiFQP2FswdtUFbulSC

--
-- PostgreSQL database dump
--

\restrict ZcHbnIqRKUe0sepzmeAQfWMPiO1LXgvJ92ykQPmmaxQXXXLV2kuOkCjp8cX1klN

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2026_10_28_000000_agregar_indices_sys_auditoria	1
2	2026_11_12_000000_agregar_activo_per_tutor_alumno	1
3	2026_11_13_000000_agregar_contrasena_temporal_users	1
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 3, true);


--
-- PostgreSQL database dump complete
--

\unrestrict ZcHbnIqRKUe0sepzmeAQfWMPiO1LXgvJ92ykQPmmaxQXXXLV2kuOkCjp8cX1klN

