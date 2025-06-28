<?php

namespace App\Http\Requests\V1\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class NewsletterEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:newsletter_emails,email',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('messages.validation.required', [
                'attribute' => __('messages.validation.attributes.email')
            ]),
            'email.email'    => __('messages.validation.email', [
                'attribute' => __('messages.validation.attributes.email')
            ]),
            'email.unique'   => __('messages.validation.unique', [
                'attribute' => __('messages.validation.attributes.email')
            ]),
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
