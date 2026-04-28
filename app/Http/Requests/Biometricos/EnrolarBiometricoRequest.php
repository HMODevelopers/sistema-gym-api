<?php

namespace App\Http\Requests\Biometricos;

use App\Models\ClienteBiometrico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class EnrolarBiometricoRequest extends FormRequest
{
    private const DEDOS = [
        'PULGAR_DERECHO',
        'INDICE_DERECHO',
        'MEDIO_DERECHO',
        'ANULAR_DERECHO',
        'MENIQUE_DERECHO',
        'PULGAR_IZQUIERDO',
        'INDICE_IZQUIERDO',
        'MEDIO_IZQUIERDO',
        'ANULAR_IZQUIERDO',
        'MENIQUE_IZQUIERDO',
        'NO_ESPECIFICADO',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $table = ClienteBiometrico::tableName();

        $rules = [
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')],
            'identificador_biometrico' => [
                'required',
                'string',
                'max:255',
                Rule::unique($table, 'identificador_biometrico')->where(function ($query): void {
                    if (Schema::hasColumn(ClienteBiometrico::tableName(), 'activo')) {
                        $query->where('activo', true);
                    }
                }),
            ],
        ];

        if (Schema::hasColumn($table, 'dispositivo_id')) {
            $rules['dispositivo_id'] = ['nullable', 'integer', Rule::exists('dispositivos', 'id')];
        }

        if (Schema::hasColumn($table, 'dedo')) {
            $rules['dedo'] = ['nullable', 'string', Rule::in(self::DEDOS)];
        }

        if (Schema::hasColumn($table, 'es_principal')) {
            $rules['es_principal'] = ['sometimes', 'boolean'];
        }

        if (Schema::hasColumn($table, 'calidad_lectura')) {
            $rules['calidad_lectura'] = ['nullable', 'integer', 'min:0', 'max:100'];
        }

        if (Schema::hasColumn($table, 'observaciones')) {
            $rules['observaciones'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'dispositivo_id.exists' => 'El dispositivo seleccionado no existe.',
            'identificador_biometrico.required' => 'El identificador biométrico es obligatorio.',
            'identificador_biometrico.unique' => 'El identificador biométrico ya está registrado en un biométrico activo.',
            'dedo.in' => 'El dedo seleccionado no es válido.',
            'calidad_lectura.min' => 'La calidad de lectura mínima es 0.',
            'calidad_lectura.max' => 'La calidad de lectura máxima es 100.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->filled('dedo')) {
            $payload['dedo'] = mb_strtoupper(trim((string) $this->input('dedo')));
        }

        if ($this->filled('identificador_biometrico')) {
            $payload['identificador_biometrico'] = trim((string) $this->input('identificador_biometrico'));
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
