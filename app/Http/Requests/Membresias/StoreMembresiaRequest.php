<?php

namespace App\Http\Requests\Membresias;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreMembresiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')],
            'plan_id' => ['required', 'integer', Rule::exists('planes', 'id')],
        ];

        if (Schema::hasColumn('membresias', 'sucursal_id')) {
            $rules['sucursal_id'] = [
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
            $rules['fecha_inicio'] = ['nullable', 'date'];
        }

        if (Schema::hasColumn('membresias', 'fecha_vencimiento')) {
            $rules['fecha_vencimiento'] = ['nullable', 'date', 'after_or_equal:fecha_inicio'];
        }

        if (Schema::hasColumn('membresias', 'precio')) {
            $rules['precio'] = ['nullable', 'numeric', 'min:0'];
        }

        if (Schema::hasColumn('membresias', 'observaciones')) {
            $rules['observaciones'] = ['nullable', 'string'];
        }

        if (Schema::hasColumn('membresias', 'estatus')) {
            $rules['estatus'] = ['nullable', Rule::in(['ACTIVA', 'VENCIDA', 'SUSPENDIDA', 'CANCELADA', 'RENOVADA'])];
        }

        if (Schema::hasColumn('membresias', 'accesos_totales')) {
            $rules['accesos_totales'] = ['nullable', 'integer', 'min:1'];
        }

        if (Schema::hasColumn('membresias', 'activo')) {
            $rules['activo'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }
}
