<?php

namespace App\Http\Requests\Membresias;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RenovarMembresiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'plan_id' => ['nullable', 'integer', Rule::exists('planes', 'id')],
        ];

        if (Schema::hasColumn('membresias', 'fecha_inicio')) {
            $rules['fecha_inicio'] = ['nullable', 'date'];
        }

        if (Schema::hasColumn('membresias', 'precio')) {
            $rules['precio'] = ['nullable', 'numeric', 'min:0'];
        }

        if (Schema::hasColumn('membresias', 'observaciones')) {
            $rules['observaciones'] = ['nullable', 'string'];
        }

        return $rules;
    }
}
