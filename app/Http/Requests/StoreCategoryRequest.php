<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where('user_id', auth()->id())
                    ->where('type', $this->input('type'))
            ],
            'type' => [
                'required',
                'string',
                Rule::in(Category::getTypes())
            ],
            'color' => [
                'required',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
            ],
            'icon' => [
                'required',
                'string',
                'max:100'
            ],
            'is_active' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya tienes una categoría con este nombre para el tipo seleccionado.',
            'type.in' => 'El tipo de categoría debe ser: ' . implode(', ', Category::getTypes()),
            'color.regex' => 'El color debe ser un código hexadecimal válido (ej: #FF0000).',
        ];
    }
    
    // Sin prepareForValidation - más limpio
}
