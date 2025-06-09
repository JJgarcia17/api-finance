<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $budgetId = $this->route('budget');
        
        return [
            'category_id' => [
                'sometimes',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', auth('sanctum')->id()),
                Rule::unique('budgets')
                    ->where('user_id', auth('sanctum')->id())
                    ->where('category_id', $this->category_id)
                    ->where('period', $this->period ?? 'monthly')
                    ->whereNull('deleted_at')
                    ->ignore($budgetId)
            ],
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01|max:999999999.99',
            'period' => 'sometimes|in:weekly,monthly,quarterly,yearly',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
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
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }
}