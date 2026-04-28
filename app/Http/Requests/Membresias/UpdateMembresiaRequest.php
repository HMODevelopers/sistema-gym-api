<?php

namespace App\Http\Requests\Membresias;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UpdateMembresiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'plan_id' => ['sometimes', 'required', 'integer', Rule::exists('planes', 'id')],
            'cliente_id' => ['prohibited'],
        ];

        if (Schema::hasColumn('membresias', 'sucursal_id')) {
            $rules['sucursal_id'] = [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('sucursales', 'id')->where(function ($query): void {
                    if (Schema::hasColumn('sucursales', 'activo')) {
                        $query->where('activo', true);
                    }
                }),
            ];
        }

        if (Schema::hasColumn('membresias', 'fecha_inicio')) {
            $rules['fecha_inicio'] = ['sometimes', 'required', 'date'];
        }

        if (Schema::hasColumn('membresias', 'fecha_vencimiento')) {
            $rules['fecha_vencimiento'] = ['sometimes', 'nullable', 'date', 'after_or_equal:fecha_inicio'];
        }

        if (Schema::hasColumn('membresias', 'precio')) {
            $rules['precio'] = ['sometimes', 'nullable', 'numeric', 'min:0'];
        }

        if (Schema::hasColumn('membresias', 'observaciones')) {
            $rules['observaciones'] = ['sometimes', 'nullable', 'string'];
        }

        if (Schema::hasColumn('membresias', 'accesos_totales')) {
            $rules['accesos_totales'] = ['sometimes', 'nullable', 'integer', 'min:1'];
        }

        if (Schema::hasColumn('membresias', 'accesos_usados')) {
            $rules['accesos_usados'] = ['sometimes', 'nullable', 'integer', 'min:0'];
        }

        if (Schema::hasColumn('membresias', 'estatus')) {
            $rules['estatus'] = ['prohibited'];
        }

        if (Schema::hasColumn('membresias', 'activo')) {
            $rules['activo'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'cliente_id.prohibited' => 'No se permite cambiar el cliente de la membresía.',
            'estatus.prohibited' => 'El estatus debe cambiarse desde los endpoints dedicados.',
        ];
    }
}
