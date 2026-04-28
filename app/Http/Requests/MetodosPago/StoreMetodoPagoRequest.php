<?php

namespace App\Http\Requests\MetodosPago;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreMetodoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nombre' => ['required', 'string', 'max:150'],
            'clave' => ['required', 'string', 'max:50', Rule::unique('metodos_pago', 'clave')],
        ];

        if (Schema::hasColumn('metodos_pago', 'descripcion')) {
            $rules['descripcion'] = ['nullable', 'string', 'max:255'];
        }

        if (Schema::hasColumn('metodos_pago', 'requiere_referencia')) {
            $rules['requiere_referencia'] = ['sometimes', 'boolean'];
        }

        if (Schema::hasColumn('metodos_pago', 'activo')) {
            $rules['activo'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'clave.required' => 'El campo clave es obligatorio.',
            'clave.unique' => 'La clave ya está en uso.',
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
