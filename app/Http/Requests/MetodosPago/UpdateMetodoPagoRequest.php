<?php

namespace App\Http\Requests\MetodosPago;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UpdateMetodoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $metodoPagoId = $this->route('metodoPago');

        $rules = [
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'clave' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('metodos_pago', 'clave')->ignore($metodoPagoId)],
        ];

        if (Schema::hasColumn('metodos_pago', 'descripcion')) {
            $rules['descripcion'] = ['sometimes', 'nullable', 'string', 'max:255'];
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
