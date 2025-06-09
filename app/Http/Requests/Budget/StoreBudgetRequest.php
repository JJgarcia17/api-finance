<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', auth('sanctum')->id()),
                Rule::unique('budgets')
                    ->where('user_id', auth('sanctum')->id())
                    ->where('category_id', $this->category_id)
                    ->where('period', $this->period ?? 'monthly')
                    ->whereNull('deleted_at')
            ],
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'period' => 'required|in:weekly,monthly,quarterly,yearly',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.unique' => 'Ya existe un presupuesto activo para esta categoría en el período seleccionado.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'amount.max' => 'El monto no puede exceder 999,999,999.99.',
            'start_date.after_or_equal' => 'La fecha de inicio debe ser hoy o posterior.',
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }
}