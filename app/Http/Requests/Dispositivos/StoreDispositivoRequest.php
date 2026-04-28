<?php

namespace App\Http\Requests\Dispositivos;

use App\Enums\DispositivoEstatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreDispositivoRequest extends FormRequest
{
    private const TIPOS = [
        'PC_RECEPCION',
        'LECTOR_HUELLA',
        'ESTACION_ENROLAMIENTO',
        'TERMINAL_ACCESO',
        'OTRO',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'sucursal_id' => [
                'required',
                'integer',
                Rule::exists('sucursales', 'id')->where(function ($query): void {
                    if (Schema::hasColumn('sucursales', 'activo')) {
                        $query->where('activo', true);
                    }
                }),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'clave' => ['required', 'string', 'max:80', Rule::unique('dispositivos', 'clave')],
            'identificador' => ['required', 'string', 'max:150', Rule::unique('dispositivos', 'identificador')],
            'tipo' => ['required', Rule::in(self::TIPOS)],
        ];

        if (Schema::hasColumn('dispositivos', 'descripcion')) {
            $rules['descripcion'] = ['nullable', 'string', 'max:255'];
        }

        if (Schema::hasColumn('dispositivos', 'ubicacion')) {
            $rules['ubicacion'] = ['nullable', 'string', 'max:150'];
        }

        if (Schema::hasColumn('dispositivos', 'ip')) {
            $rules['ip'] = ['nullable', 'string', 'max:45'];
        }

        if (Schema::hasColumn('dispositivos', 'sistema_operativo')) {
            $rules['sistema_operativo'] = ['nullable', 'string', 'max:100'];
        }

        if (Schema::hasColumn('dispositivos', 'estatus')) {
            $rules['estatus'] = ['nullable', Rule::in(array_column(DispositivoEstatus::cases(), 'value'))];
        }

        if (Schema::hasColumn('dispositivos', 'activo')) {
            $rules['activo'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'sucursal_id.required' => 'El campo sucursal es obligatorio.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe o está inactiva.',
            'nombre.required' => 'El campo nombre es obligatorio.',
            'clave.required' => 'El campo clave es obligatorio.',
            'clave.unique' => 'La clave ya está en uso.',
            'identificador.required' => 'El campo identificador es obligatorio.',
            'identificador.unique' => 'El identificador ya está en uso.',
            'tipo.required' => 'El campo tipo es obligatorio.',
            'tipo.in' => 'El tipo seleccionado no es válido.',
            'estatus.in' => 'El estatus seleccionado no es válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->filled('clave')) {
            $payload['clave'] = mb_strtoupper(trim((string) $this->input('clave')));
        }

        if ($this->filled('tipo')) {
            $payload['tipo'] = mb_strtoupper(trim((string) $this->input('tipo')));
        }

        if ($this->filled('estatus')) {
            $payload['estatus'] = mb_strtoupper(trim((string) $this->input('estatus')));
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
