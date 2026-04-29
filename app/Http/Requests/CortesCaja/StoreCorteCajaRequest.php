<?php

namespace App\Http\Requests\CortesCaja;

class StoreCorteCajaRequest extends CalcularCorteCajaRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'efectivo_contado' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);
    }
}
