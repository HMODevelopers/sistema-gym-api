<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;

class CancelarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de cancelación es obligatorio.',
            'motivo.max' => 'El motivo de cancelación no debe exceder 255 caracteres.',
        ];
    }
}
