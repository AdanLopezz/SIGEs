<?php

namespace App\Http\Requests\Prefectura;

class RegistrarAccesoRequest extends PrefecturaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('acceso.registrar') ?? false;
    }

    public function rules(): array
    {
        return ['codigo' => ['required', 'string', 'max:500']];
    }

    /**
     * El lector USB emula un teclado en inglés y puede cambiar ":" o "/" en un teclado en español,
     * así que se quitan los símbolos y se toman los últimos 40 caracteres (el token va al final de la URL).
     */
    public function token(): ?string
    {
        $limpio = preg_replace('/[^A-Za-z0-9]/', '', (string) $this->validated('codigo'));

        return strlen($limpio) >= 40 ? substr($limpio, -40) : null;
    }
}
