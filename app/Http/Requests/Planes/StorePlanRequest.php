<?php

namespace App\Http\Requests\Planes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nombre' => ['required', 'string', 'max:150'],
            'clave' => ['required', 'string', 'max:50', Rule::unique('planes', 'clave')],
            'activo' => ['sometimes', 'boolean'],
        ];

        if (Schema::hasColumn('planes', 'descripcion')) {
            $rules['descripcion'] = ['nullable', 'string', 'max:255'];
        }

        if (Schema::hasColumn('planes', 'precio')) {
            $rules['precio'] = ['required', 'numeric', 'min:0'];
        }

        if (Schema::hasColumn('planes', 'duracion_dias')) {
            $rules['duracion_dias'] = ['nullable', 'integer', 'min:1'];
        }

        $hasTipoPlan = Schema::hasColumn('planes', 'tipo_plan');
        if ($hasTipoPlan) {
            $rules['tipo_plan'] = ['required', 'string', Rule::in(['TIEMPO', 'ACCESOS', 'MIXTO'])];
        }

        $hasAccesos = Schema::hasColumn('planes', 'accesos_incluidos');
        if ($hasAccesos) {
            $rules['accesos_incluidos'] = ['nullable', 'integer', 'min:1'];
        }

        if ($hasTipoPlan && Schema::hasColumn('planes', 'duracion_dias')) {
            $rules['duracion_dias'][] = Rule::requiredIf(in_array($this->input('tipo_plan'), ['TIEMPO', 'MIXTO'], true));
        }

        if ($hasTipoPlan && $hasAccesos) {
            $rules['accesos_incluidos'][] = Rule::requiredIf(in_array($this->input('tipo_plan'), ['ACCESOS', 'MIXTO'], true));
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'clave.required' => 'El campo clave es obligatorio.',
            'clave.unique' => 'La clave ya está en uso.',
            'precio.required' => 'El campo precio es obligatorio.',
            'precio.numeric' => 'El campo precio debe ser numérico.',
            'tipo_plan.required' => 'El campo tipo de plan es obligatorio.',
            'tipo_plan.in' => 'El tipo de plan seleccionado no es válido.',
            'duracion_dias.required' => 'La duración en días es obligatoria para este tipo de plan.',
            'accesos_incluidos.required' => 'Los accesos incluidos son obligatorios para este tipo de plan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('clave')) {
            $this->merge([
                'clave' => mb_strtoupper(trim((string) $this->input('clave'))),
            ]);
        }

        if ($this->filled('tipo_plan')) {
            $this->merge([
                'tipo_plan' => mb_strtoupper(trim((string) $this->input('tipo_plan'))),
            ]);
        }
    }
}
