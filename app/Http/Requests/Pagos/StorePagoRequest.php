<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')],
            'metodo_pago_id' => ['required', 'integer', Rule::exists('metodos_pago', 'id')],
            'concepto' => ['required', 'string', Rule::in(['INSCRIPCION', 'MEMBRESIA', 'RENOVACION', 'PRODUCTO', 'SERVICIO', 'AJUSTE', 'OTRO'])],
            'monto' => ['required', 'numeric', 'min:0.01'],
        ];

        if (Schema::hasColumn('pagos', 'membresia_id')) {
            $rules['membresia_id'] = ['nullable', 'integer', Rule::exists('membresias', 'id')];
        }

        if (Schema::hasColumn('pagos', 'sucursal_id')) {
            $rules['sucursal_id'] = ['nullable', 'integer', Rule::exists('sucursales', 'id')];
        }

        if (Schema::hasColumn('pagos', 'fecha_pago')) {
            $rules['fecha_pago'] = ['nullable', 'date'];
        }

        if (Schema::hasColumn('pagos', 'referencia')) {
            $rules['referencia'] = ['nullable', 'string', 'max:100'];
        }

        if (Schema::hasColumn('pagos', 'observaciones')) {
            $rules['observaciones'] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'metodo_pago_id.required' => 'El método de pago es obligatorio.',
            'metodo_pago_id.exists' => 'El método de pago seleccionado no existe.',
            'concepto.required' => 'El concepto es obligatorio.',
            'concepto.in' => 'El concepto seleccionado no es válido.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.min' => 'El monto debe ser mayor a cero.',
            'membresia_id.exists' => 'La membresía seleccionada no existe.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe.',
        ];
    }
}
