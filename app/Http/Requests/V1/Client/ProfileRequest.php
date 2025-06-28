<?php

namespace App\Http\Requests\V1\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|min:2|max:255',
            'gender'        => 'nullable|in:male,female,other',
            'birthdate'     => 'nullable|date',
            'occupation_id' => 'required|exists:occupations,id',
            'phone'         => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => __('messages.validation.required', [
                'attribute' => __('messages.validation.attributes.name')
            ]),
            'name.string'       => __('messages.validation.string', [
                'attribute' => __('messages.validation.attributes.name')
            ]),
            'name.min'          => __('messages.validation.min.string', [
                'attribute' => __('messages.validation.attributes.name'),
                'min' => 2
            ]),
            'name.max'          => __('messages.validation.max.string', [
                'attribute' => __('messages.validation.attributes.name'),
                'max' => 255
            ]),

            'gender.in'         => __('messages.validation.in', [
                'attribute' => __('messages.validation.attributes.gender')
            ]),

            'birthdate.date'    => __('messages.validation.date', [
                'attribute' => __('messages.validation.attributes.birthdate')
            ]),

            'occupation_id.required' => __('messages.validation.required', [
                'attribute' => __('messages.validation.attributes.occupation_id')
            ]),
            'occupation_id.exists'   => __('messages.validation.exists', [
                'attribute' => __('messages.validation.attributes.occupation_id')
            ]),

            'phone.string'      => __('messages.validation.string', [
                'attribute' => __('messages.validation.attributes.phone')
            ]),
            'phone.max'         => __('messages.validation.max.string', [
                'attribute' => __('messages.validation.attributes.phone'),
                'max' => 20
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
