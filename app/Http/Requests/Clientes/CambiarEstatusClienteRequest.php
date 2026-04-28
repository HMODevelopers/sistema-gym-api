<?php

namespace App\Http\Requests\Clientes;

use App\Enums\ClienteEstatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstatusClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estatus' => ['required', Rule::in(array_column(ClienteEstatus::cases(), 'value'))],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'estatus.required' => 'El campo estatus es obligatorio.',
            'estatus.in' => 'El estatus seleccionado no es válido.',
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
