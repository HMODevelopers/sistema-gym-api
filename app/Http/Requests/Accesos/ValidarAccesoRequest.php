<?php

namespace App\Http\Requests\Accesos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ValidarAccesoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'cliente_id' => ['required_without:codigo', 'nullable', 'integer', Rule::exists('clientes', 'id')],
            'codigo' => ['required_without:cliente_id', 'nullable', 'string', 'max:100'],
            'sucursal_id' => ['required', 'integer', Rule::exists('sucursales', 'id')],
            'metodo' => ['required', 'string', Rule::in(['MANUAL', 'CODIGO', 'QR', 'HUELLA'])],
        ];

        if (Schema::hasColumn('accesos', 'observaciones')) {
            $rules['observaciones'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'cliente_id.required_without' => 'Debes enviar cliente_id o codigo.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'codigo.required_without' => 'Debes enviar codigo o cliente_id.',
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe.',
            'metodo.required' => 'El método es obligatorio.',
            'metodo.in' => 'El método seleccionado no es válido.',
        ];
    }
}
