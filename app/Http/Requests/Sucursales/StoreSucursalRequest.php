<?php

namespace App\Http\Requests\Sucursales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'clave' => ['required', 'string', 'max:50', Rule::unique('sucursales', 'clave')],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:150'],
            'es_principal' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'clave.required' => 'El campo clave es obligatorio.',
            'clave.unique' => 'La clave ya está en uso.',
            'correo.email' => 'El campo correo debe ser un correo electrónico válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('clave')) {
            $this->merge([
                'clave' => mb_strtoupper(trim((string) $this->input('clave'))),
            ]);
        }
    }
}
