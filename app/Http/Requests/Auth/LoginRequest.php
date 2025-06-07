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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    // Agregar esta función para rechazar campos extra
    public function prepareForValidation()
    {
        // Solo permitir email y password
        $this->replace($this->only(['email', 'password']));
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe tener un formato válido',
            'password.required' => 'La contraseña es obligatoria',
        ];
    }
}