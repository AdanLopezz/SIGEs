<?php

namespace App\Http\Requests\ControlEscolar;

use App\Models\PerPersona;
use Illuminate\Foundation\Http\FormRequest;

abstract class ControlEscolarRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['curp', 'tutor_curp', 'matricula'] as $campo) {
            if (is_string($this->input($campo))) {
                $this->merge([$campo => mb_strtoupper(trim($this->input($campo)))]);
            }
        }
        foreach (['email_personal', 'email_institucional', 'tutor_email_personal'] as $campo) {
            if (is_string($this->input($campo))) {
                $this->merge([$campo => mb_strtolower(trim($this->input($campo)))]);
            }
        }
    }

    // Reglas de CURP comunes; $extra agrega comprobaciones propias de cada formulario.
    protected function reglasCurp(?callable $extra = null): array
    {
        return array_values(array_filter(['bail', 'required', 'string', 'size:18', 'regex:'.PerPersona::CURP_REGEX,
            function ($atributo, $valor, $fallar) {
                if ($valor === PerPersona::CURP_SISTEMA) {
                    $fallar('Esta CURP no se puede registrar.');
                }
            }, $extra]));
    }

    // Datos de una persona; con $prefijo para el tutor del alta (tutor_nombre…).
    protected function reglasPersona(string $prefijo = '', bool $domicilioOpcional = false): array
    {
        $domicilio = $domicilioOpcional ? ["exclude_if:{$prefijo}mismo_domicilio,1", 'required'] : ['required'];

        return [
            "{$prefijo}nombre" => ['required', 'string', 'max:100'],
            "{$prefijo}primer_apellido" => ['required', 'string', 'max:100'],
            "{$prefijo}segundo_apellido" => ['nullable', 'string', 'max:100'],
            "{$prefijo}email_personal" => ['required', 'email', 'max:150'],
            "{$prefijo}telefono" => ['required', 'digits:10'],
            "{$prefijo}calle_numero" => [...$domicilio, 'string', 'max:150'],
            "{$prefijo}colonia" => [...$domicilio, 'string', 'max:100'],
            "{$prefijo}municipio" => [...$domicilio, 'string', 'max:100'],
            "{$prefijo}codigo_postal" => [...$domicilio, 'digits:5'],
        ];
    }

    public function attributes(): array
    {
        $persona = ['curp' => 'CURP', 'nombre' => 'nombre', 'primer_apellido' => 'primer apellido', 'segundo_apellido' => 'segundo apellido',
            'email_personal' => 'correo personal', 'telefono' => 'teléfono', 'calle_numero' => 'calle y número', 'colonia' => 'colonia',
            'municipio' => 'municipio', 'codigo_postal' => 'código postal', 'parentesco' => 'parentesco'];
        $tutor = [];
        foreach ($persona as $campo => $nombre) {
            $tutor["tutor_{$campo}"] = "{$nombre} del tutor";
        }

        return $persona + $tutor + ['matricula' => 'matrícula', 'email_institucional' => 'correo institucional',
            'aca_carrera_id' => 'carrera', 'fecha_ingreso' => 'fecha de ingreso', 'aca_grupo_escolar_id' => 'grupo',
            'nss' => 'NSS', 'tipo_sangre' => 'tipo de sangre', 'alergias' => 'alergias', 'condiciones_medicas' => 'condiciones médicas',
            'es_contacto_emergencia' => 'contacto de emergencia', 'recibe_notificaciones' => 'recibe notificaciones',
            'expediente' => 'expediente', 'buscar' => 'búsqueda', 'grupo' => 'grupo', 'carrera' => 'carrera',
            'semestre' => 'semestre', 'estado' => 'estado', 'motivo' => 'motivo del cambio'];
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no debe superar :max caracteres.', 'email' => 'Escribe un correo válido en :attribute.',
            'digits' => 'El campo :attribute debe tener :digits dígitos.', 'unique' => 'El valor de :attribute ya está registrado.',
            'exists' => 'Selecciona una opción válida para :attribute.', 'integer' => 'Selecciona una opción válida para :attribute.',
            'in' => 'Selecciona una opción válida para :attribute.', 'between' => 'El campo :attribute debe estar entre :min y :max.',
            'boolean' => 'El campo :attribute no es válido.', 'date_format' => 'El campo :attribute no tiene un formato válido.',
            'before_or_equal' => 'La :attribute no puede ser una fecha futura.', 'different' => 'La CURP del tutor debe ser distinta a la del alumno.',
            'size' => 'La :attribute debe tener :size caracteres.', 'regex' => 'La :attribute no tiene un formato válido.',
            'email_institucional.unique' => 'Este correo institucional ya está registrado.',
            'aca_grupo_escolar_id.exists' => 'Selecciona un grupo del periodo activo y de la carrera del alumno.',
            'aca_carrera_id.exists' => 'Selecciona una carrera activa.'];
    }
}
