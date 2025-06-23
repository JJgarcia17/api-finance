<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'from_account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id)
            ],
            'to_account_id' => [
                'required',
                'integer',
                'different:from_account_id',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id)
            ],
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'from_account_id.required' => 'La cuenta origen es requerida.',
            'from_account_id.exists' => 'La cuenta origen seleccionada no existe o no te pertenece.',
            'to_account_id.required' => 'La cuenta destino es requerida.',
            'to_account_id.exists' => 'La cuenta destino seleccionada no existe o no te pertenece.',
            'to_account_id.different' => 'La cuenta destino debe ser diferente a la cuenta origen.',
            'amount.required' => 'El monto es requerido.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'amount.max' => 'El monto no puede exceder 999,999,999.99.',
            'description.max' => 'La descripción no puede exceder 255 caracteres.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'from_account_id' => 'cuenta origen',
            'to_account_id' => 'cuenta destino',
            'amount' => 'monto',
            'description' => 'descripción',
        ];
    }
}