<?php

namespace App\Http\Requests\Planes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planId = $this->route('plan');

        $rules = [
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'clave' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('planes', 'clave')->ignore($planId)],
            'activo' => ['sometimes', 'boolean'],
        ];

        if (Schema::hasColumn('planes', 'descripcion')) {
            $rules['descripcion'] = ['sometimes', 'nullable', 'string', 'max:255'];
        }

        if (Schema::hasColumn('planes', 'precio')) {
            $rules['precio'] = ['sometimes', 'required', 'numeric', 'min:0'];
        }

        if (Schema::hasColumn('planes', 'duracion_dias')) {
            $rules['duracion_dias'] = ['sometimes', 'nullable', 'integer', 'min:1'];
        }

        $hasTipoPlan = Schema::hasColumn('planes', 'tipo_plan');
        if ($hasTipoPlan) {
            $rules['tipo_plan'] = ['sometimes', 'required', 'string', Rule::in(['TIEMPO', 'ACCESOS', 'MIXTO'])];
        }

        $hasAccesos = Schema::hasColumn('planes', 'accesos_incluidos');
        if ($hasAccesos) {
            $rules['accesos_incluidos'] = ['sometimes', 'nullable', 'integer', 'min:1'];
        }

        $tipoPlan = $this->input('tipo_plan');
        if ($hasTipoPlan && Schema::hasColumn('planes', 'duracion_dias') && in_array($tipoPlan, ['TIEMPO', 'MIXTO'], true)) {
            $rules['duracion_dias'] = ['required', 'integer', 'min:1'];
        }

        if ($hasTipoPlan && $hasAccesos && in_array($tipoPlan, ['ACCESOS', 'MIXTO'], true)) {
            $rules['accesos_incluidos'] = ['required', 'integer', 'min:1'];
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
