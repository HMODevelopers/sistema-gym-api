<?php

namespace App\Http\Requests\CortesCaja;

use Illuminate\Foundation\Http\FormRequest;

class CancelarCorteCajaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'max:255']];
    }
}
