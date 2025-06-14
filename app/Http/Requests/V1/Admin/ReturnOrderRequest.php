<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ReturnOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Use policy or auth logic as needed
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'reason' => 'nullable|string|max:1000',

            'products' => 'nullable|array',
            'products.*.variant_id' => 'required|exists:variants,id',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => __('messages.return_order.order_id_required'),
            'order_id.exists' => __('messages.return_order.order_id_exists'),
            'reason.string' => __('messages.return_order.reason_string'),
            'reason.max' => __('messages.return_order.reason_max'),

            'products.array' => __('messages.return_order.products_array'),
            'products.*.variant_id.required' => __('messages.return_order.variant_id_required'),
            'products.*.variant_id.exists' => __('messages.return_order.variant_id_exists'),
            'products.*.quantity.required' => __('messages.return_order.quantity_required'),
            'products.*.quantity.integer' => __('messages.return_order.quantity_integer'),
            'products.*.quantity.min' => __('messages.return_order.quantity_min'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 200));
    }
}
