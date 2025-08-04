<?php

namespace App\Http\Requests\V1\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.product')]),
            'product_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.validation.attributes.product')]),

            'rating.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.rating')]),
            'rating.integer' => __('messages.validation.integer', ['attribute' => __('messages.validation.attributes.rating')]),
            'rating.min' => __('messages.validation.min.numeric', ['attribute' => __('messages.validation.attributes.rating'), 'min' => 1]),
            'rating.max' => __('messages.validation.max.numeric', ['attribute' => __('messages.validation.attributes.rating'), 'max' => 5]),

            'comment.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.comment')]),
            'comment.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.comment'), 'max' => 1000]),
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
