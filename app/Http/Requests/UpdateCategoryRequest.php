<?php

namespace App\Http\Requests;

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
        $category = $this->route('category');
        
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where('user_id', auth()->user()->id)
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
            'is_active' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe una categoría con este nombre para este tipo.',
            'type.in' => 'El tipo de categoría debe ser: ' . implode(', ', Category::getTypes()),
            'color.regex' => 'El color debe ser un código hexadecimal válido.',
            'icon.max' => 'El icono no puede tener más de 100 caracteres.',
        ];
    }
}
