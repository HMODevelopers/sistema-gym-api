<?php

namespace App\Http\Requests\CortesCaja;

use Illuminate\Foundation\Http\FormRequest;

class CalcularCorteCajaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
        ];
    }
}
