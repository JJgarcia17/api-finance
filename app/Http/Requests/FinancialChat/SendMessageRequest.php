<?php

namespace App\Http\Requests\FinancialChat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'message' => [
                'required',
                'string',
                'min:1',
                'max:1000'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'message.required' => 'El mensaje es obligatorio.',
            'message.string' => 'El mensaje debe ser un texto válido.',
            'message.min' => 'El mensaje debe tener al menos 1 carácter.',
            'message.max' => 'El mensaje no puede exceder 1000 caracteres.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('message')) {
            $this->merge([
                'message' => trim($this->message)
            ]);
        }
    }
}
