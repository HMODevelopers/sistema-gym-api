<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clienteId = $this->route('cliente');

        $rules = [
            'sucursal_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('sucursales', 'id')->where(function ($query): void {
                    if (Schema::hasColumn('sucursales', 'activo')) {
                        $query->where('activo', true);
                    }
                }),
            ],
            'nombre' => ['sometimes', 'required', 'string', 'max:100'],
            'apellido_paterno' => ['sometimes', 'required', 'string', 'max:100'],
        ];

        if (Schema::hasColumn('clientes', 'apellido_materno')) {
            $rules['apellido_materno'] = ['sometimes', 'nullable', 'string', 'max:100'];
        }

        if (Schema::hasColumn('clientes', 'telefono')) {
            $rules['telefono'] = ['sometimes', 'nullable', 'string', 'max:30'];
        }

        if (Schema::hasColumn('clientes', 'email')) {
            $rules['email'] = ['sometimes', 'nullable', 'email', 'max:150', Rule::unique('clientes', 'email')->ignore($clienteId)];
        }

        if (Schema::hasColumn('clientes', 'fecha_nacimiento')) {
            $rules['fecha_nacimiento'] = ['sometimes', 'nullable', 'date'];
        }

        if (Schema::hasColumn('clientes', 'contacto_emergencia_nombre')) {
            $rules['contacto_emergencia_nombre'] = ['sometimes', 'nullable', 'string', 'max:150'];
        }

        if (Schema::hasColumn('clientes', 'contacto_emergencia_telefono')) {
            $rules['contacto_emergencia_telefono'] = ['sometimes', 'nullable', 'string', 'max:30'];
        }

        if (Schema::hasColumn('clientes', 'foto_url')) {
            $rules['foto_url'] = ['sometimes', 'nullable', 'string', 'max:255'];
        }

        if (Schema::hasColumn('clientes', 'fecha_inscripcion')) {
            $rules['fecha_inscripcion'] = ['sometimes', 'nullable', 'date'];
        }

        if (Schema::hasColumn('clientes', 'notas')) {
            $rules['notas'] = ['sometimes', 'nullable', 'string'];
        }

        if (Schema::hasColumn('clientes', 'activo')) {
            $rules['activo'] = ['sometimes', 'boolean'];
        }

        if (Schema::hasColumn('clientes', 'estatus')) {
            $rules['estatus'] = ['prohibited'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'sucursal_id.exists' => 'La sucursal seleccionada no existe o está inactiva.',
            'email.email' => 'El campo email debe ser un correo electrónico válido.',
            'email.unique' => 'El email ya está en uso.',
            'estatus.prohibited' => 'El estatus debe actualizarse desde el endpoint de cambio de estatus.',
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
