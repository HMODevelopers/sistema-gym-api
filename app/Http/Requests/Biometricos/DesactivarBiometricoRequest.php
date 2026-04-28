<?php

namespace App\Http\Requests\Biometricos;

use App\Models\ClienteBiometrico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class DesactivarBiometricoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];
        $table = ClienteBiometrico::tableName();

        if (Schema::hasColumn($table, 'observaciones')) {
            $rules['motivo'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
