<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // El route model binding ya verifica que la categoría pertenece al usuario
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $categoryId = $this->route('category');
        
        // Obtener la categoría actual para acceder a sus propiedades
        $category = Category::where('user_id', auth('sanctum')->user()->id)
                          ->findOrFail($categoryId);
        
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where('user_id', auth('sanctum')->user()->id)
                    ->where('type', $this->input('type', $category->type))
                    ->ignore($category->id)
            ],
            'type' => [
                'sometimes',
                'string',
                Rule::in(Category::getTypes())
            ],
            'color' => [
                'sometimes',
                'string',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
            ],
            'icon' => [
                'sometimes',
                'string',
                'max:100'
            ],
            'description' => [
                'sometimes',
                'string',
                'max:500'
            ],
            'is_active' => [
                'sometimes',
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
            'name.unique' => 'Ya existe una categoría con este nombre y tipo.',
            'type.in' => 'El tipo debe ser income o expense.',
            'color.regex' => 'El color debe estar en formato hexadecimal válido (#RRGGBB o #RGB).',
        ];
    }
}
