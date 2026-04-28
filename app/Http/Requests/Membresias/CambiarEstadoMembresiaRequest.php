<?php

namespace App\Http\Requests\Membresias;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class CambiarEstadoMembresiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (! Schema::hasColumn('membresias', 'observaciones')) {
            return [];
        }

        return [
            'motivo' => ['nullable', 'string'],
        ];
    }
}
