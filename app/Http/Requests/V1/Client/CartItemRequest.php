<?php

namespace App\Http\Requests\V1\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class CartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => 'required|integer|exists:variants,id',
            'quantity'   => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'variant_id.required' => __('messages.validation.required', [
                'attribute' => __('messages.validation.attributes.variant_id')
            ]),
            'variant_id.integer'  => __('messages.validation.integer', [
                'attribute' => __('messages.validation.attributes.variant_id')
            ]),
            'variant_id.exists'   => __('messages.validation.exists', [
                'attribute' => __('messages.validation.attributes.variant_id')
            ]),

            'quantity.required'   => __('messages.validation.required', [
                'attribute' => __('messages.validation.attributes.quantity')
            ]),
            'quantity.integer'    => __('messages.validation.integer', [
                'attribute' => __('messages.validation.attributes.quantity')
            ]),
            'quantity.min'        => __('messages.validation.min.string', [
                'attribute' => __('messages.validation.attributes.quantity'),
                'min' => 1
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
