<?php

namespace App\Http\Requests\Clientes;

use App\Enums\ClienteEstatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
{
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
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
        ];

        if (Schema::hasColumn('clientes', 'apellido_materno')) {
            $rules['apellido_materno'] = ['nullable', 'string', 'max:100'];
        }

        if (Schema::hasColumn('clientes', 'telefono')) {
            $rules['telefono'] = ['nullable', 'string', 'max:30'];
        }

        if (Schema::hasColumn('clientes', 'email')) {
            $rules['email'] = ['nullable', 'email', 'max:150', Rule::unique('clientes', 'email')];
        }

        if (Schema::hasColumn('clientes', 'fecha_nacimiento')) {
            $rules['fecha_nacimiento'] = ['nullable', 'date'];
        }

        if (Schema::hasColumn('clientes', 'contacto_emergencia_nombre')) {
            $rules['contacto_emergencia_nombre'] = ['nullable', 'string', 'max:150'];
        }

        if (Schema::hasColumn('clientes', 'contacto_emergencia_telefono')) {
            $rules['contacto_emergencia_telefono'] = ['nullable', 'string', 'max:30'];
        }

        if (Schema::hasColumn('clientes', 'foto_url')) {
            $rules['foto_url'] = ['nullable', 'string', 'max:255'];
        }

        if (Schema::hasColumn('clientes', 'fecha_inscripcion')) {
            $rules['fecha_inscripcion'] = ['nullable', 'date'];
        }

        if (Schema::hasColumn('clientes', 'estatus')) {
            $rules['estatus'] = ['nullable', Rule::in(array_column(ClienteEstatus::cases(), 'value'))];
        }

        if (Schema::hasColumn('clientes', 'notas')) {
            $rules['notas'] = ['nullable', 'string'];
        }

        if (Schema::hasColumn('clientes', 'activo')) {
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
            'apellido_paterno.required' => 'El campo apellido paterno es obligatorio.',
            'email.email' => 'El campo email debe ser un correo electrónico válido.',
            'email.unique' => 'El email ya está en uso.',
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
