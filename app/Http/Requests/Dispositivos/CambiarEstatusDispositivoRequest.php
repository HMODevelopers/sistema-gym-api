<?php

namespace App\Http\Requests\Dispositivos;

use App\Enums\DispositivoEstatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstatusDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estatus' => ['required', Rule::in(array_column(DispositivoEstatus::cases(), 'value'))],
            'motivo' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'estatus.required' => 'El campo estatus es obligatorio.',
            'estatus.in' => 'El estatus seleccionado no es válido.',
            'motivo.max' => 'El motivo no debe exceder 255 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('estatus')) {
            $this->merge([
                'estatus' => mb_strtoupper(trim((string) $this->input('estatus'))),
            ]);
        }
    }
}
