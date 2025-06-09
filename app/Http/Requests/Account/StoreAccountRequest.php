<?php

namespace App\Http\Requests\Account;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts', 'name')
                    ->where('user_id', auth('sanctum')->id())
                    ->whereNull('deleted_at')
            ],
            'type' => [
                'required',
                'string',
                Rule::in(Account::TYPES)
            ],
            'currency' => [
                'required',
                'string',
                'size:3',
                Rule::in(Account::CURRENCIES)
            ],
            'initial_balance' => [
                'required',
                'numeric',
                'min:-999999999.99',
                'max:999999999.99'
            ],
            'current_balance' => [
                'nullable',
                'numeric',
                'min:-999999999.99',
                'max:999999999.99'
            ],
            'color' => [
                'required',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/'
            ],
            'icon' => [
                'required',
                'string',
                'max:50'
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'is_active' => [
                'boolean'
            ],
            'include_in_total' => [
                'boolean'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la cuenta es obligatorio.',
            'name.unique' => 'Ya tienes una cuenta con este nombre.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'type.required' => 'El tipo de cuenta es obligatorio.',
            'type.in' => 'El tipo de cuenta seleccionado no es válido.',
            'currency.required' => 'La moneda es obligatoria.',
            'currency.size' => 'La moneda debe tener exactamente 3 caracteres.',
            'currency.in' => 'La moneda seleccionada no es válida.',
            'initial_balance.required' => 'El saldo inicial es obligatorio.',
            'initial_balance.numeric' => 'El saldo inicial debe ser un número.',
            'initial_balance.min' => 'El saldo inicial no puede ser menor a -999,999,999.99.',
            'initial_balance.max' => 'El saldo inicial no puede ser mayor a 999,999,999.99.',
            'current_balance.numeric' => 'El saldo actual debe ser un número.',
            'current_balance.min' => 'El saldo actual no puede ser menor a -999,999,999.99.',
            'current_balance.max' => 'El saldo actual no puede ser mayor a 999,999,999.99.',
            'color.required' => 'El color es obligatorio.',
            'color.regex' => 'El color debe tener un formato hexadecimal válido (#RRGGBB).',
            'icon.required' => 'El icono es obligatorio.',
            'icon.max' => 'El icono no puede tener más de 50 caracteres.',
            'description.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
            'include_in_total.boolean' => 'Incluir en total debe ser verdadero o falso.'
        ];
    }
}