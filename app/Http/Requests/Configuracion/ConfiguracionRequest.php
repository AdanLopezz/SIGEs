<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

abstract class ConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('configuracion.administrar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['clave', 'clave_cct'] as $campo) {
            if (is_string($this->input($campo))) {
                $this->merge([$campo => mb_strtoupper(trim($this->input($campo)))]);
            }
        }
    }

    public function attributes(): array
    {
        return ['clave_cct' => 'CCT', 'nombre' => 'nombre', 'nombre_corto' => 'nombre corto',
            'calificacion_minima' => 'calificación mínima', 'clave' => 'clave',
            'fecha_inicio' => 'fecha de inicio', 'fecha_fin' => 'fecha de fin', 'fecha' => 'fecha',
            'motivo' => 'motivo', 'hora_entrada' => 'hora de entrada', 'hora_salida' => 'hora de salida',
            'tolerancia_minutos' => 'tolerancia', 'limite_retardo_min' => 'límite de retardo',
            'tipo' => 'tipo', 'total_semestres' => 'total de semestres', 'semestre' => 'semestre',
            'horas_semana' => 'horas por semana', 'capacidad' => 'capacidad', 'buscar' => 'búsqueda'];
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'string' => 'El campo :attribute debe ser texto.',
            'max.string' => 'El campo :attribute no debe superar :max caracteres.',
            'max.numeric' => 'El campo :attribute no debe ser mayor que :max.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'numeric' => 'El campo :attribute debe ser numérico.', 'between' => 'El campo :attribute debe estar entre :min y :max.',
            'unique' => 'El valor de :attribute ya está registrado.', 'in' => 'Selecciona una opción válida para :attribute.',
            'date_format' => 'El campo :attribute no tiene un formato válido.',
            'after' => 'El campo :attribute debe ser posterior a :date.',
            'gte' => 'El campo :attribute debe ser mayor o igual que :value.',
            'decimal' => 'El campo :attribute admite como máximo dos decimales.',
            'clave.regex' => 'La clave del periodo debe tener el formato 2026-1 o 2026-2.',
            'clave_cct.regex' => 'La CCT no tiene un formato válido (ej. 15ETC0001Z)'];
    }
}
