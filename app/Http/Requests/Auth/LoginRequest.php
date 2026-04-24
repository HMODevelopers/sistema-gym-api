<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'El campo login es obligatorio.',
            'login.string' => 'El campo login debe ser una cadena de texto.',
            'login.max' => 'El campo login no puede superar los :max caracteres.',
            'password.required' => 'El campo password es obligatorio.',
            'password.string' => 'El campo password debe ser una cadena de texto.',
            'password.max' => 'El campo password no puede superar los :max caracteres.',
        ];
    }
}
