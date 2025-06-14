<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'arrangement' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'arrangement.integer' => __('messages.product_image.arrangement_integer'),
            'arrangement.min' => __('messages.product_image.arrangement_min'),
            'is_active.boolean' => __('messages.product_image.is_active_boolean'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => __('messages.validation_failed'),
            'errors' => $validator->errors()
        ], 422));
    }
}
