<?php

namespace App\Http\Requests\V1\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'is_view' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.name')]),
            'name.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.name')]),
            'name.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.name'), 'max' => 100]),

            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
            'email.email' => __('messages.validation.email', ['attribute' => __('messages.validation.attributes.email')]),
            'email.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.email'), 'max' => 150]),

            'subject.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.subject')]),
            'subject.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.subject')]),
            'subject.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.subject'), 'max' => 255]),

            'message.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.message')]),
            'message.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.message')]),

            'is_view.boolean' => __('messages.validation.boolean', ['attribute' => __('messages.validation.attributes.is_view')]),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 200));
    }
}
