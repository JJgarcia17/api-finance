<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id)
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id)
            ],
            'type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date|before_or_equal:today',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,yearly|required_if:is_recurring,true',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.exists' => 'La cuenta seleccionada no existe o no te pertenece.',
            'category_id.exists' => 'La categoría seleccionada no existe o no te pertenece.',
            'type.in' => 'El tipo de transacción debe ser: ingreso, gasto o transferencia.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'amount.max' => 'El monto no puede exceder 999,999,999.99.',
            'transaction_date.before_or_equal' => 'La fecha de transacción no puede ser futura.',
            'recurring_frequency.required_if' => 'La frecuencia es requerida para transacciones recurrentes.'
        ];
    }
}