<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client');

        return [
            'name' => 'required|string|max:100',
            'gender' => 'nullable|in:male,female,other',
            'birthdate' => 'nullable|date',
            'occupation_id' => 'required|exists:occupations,id',
            'phone' => 'nullable|string|max:20|unique:clients,phone,' . ($clientId ?? 'NULL'),
            'email' => 'required|email|max:150|unique:clients,email,' . ($clientId ?? 'NULL'),
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.name')]),
            'name.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.name')]),
            'name.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.name'), 'max' => 100]),

            'gender.in' => __('messages.validation.in', ['attribute' => __('messages.validation.attributes.gender')]),

            'birthdate.date' => __('messages.validation.date', ['attribute' => __('messages.validation.attributes.birthdate')]),

            'occupation_id.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.occupation')]),
            'occupation_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.validation.attributes.occupation')]),

            'phone.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.phone')]),
            'phone.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.phone'), 'max' => 20]),
            'phone.unique' => __('messages.validation.unique', ['attribute' => __('messages.validation.attributes.phone')]),

            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
            'email.email' => __('messages.validation.email', ['attribute' => __('messages.validation.attributes.email')]),
            'email.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.email'), 'max' => 150]),
            'email.unique' => __('messages.validation.unique', ['attribute' => __('messages.validation.attributes.email')]),

            'is_active.boolean' => __('messages.validation.boolean', ['attribute' => __('messages.validation.attributes.is_active')]),
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
