<?php

namespace App\Http\Requests\v1;

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

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => __('messages.validation_failed'),
            'errors' => $validator->errors(),
            'request_data' => $this->all(),
        ], 422));
    }
}
