<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class PreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'address_id' => 'required|exists:addresses,id',
            'coupon_code' => 'nullable|string|exists:coupons,code',
            'notes' => 'nullable|string|max:255',
            'payment_method' => 'required|integer|between:0,0',
            'payment_status' => 'required|integer|between:0,3',
            'products' => 'required|array|min:1',
            'products.*.variant_id' => 'required|exists:variants,id',
            'products.*.quantity' => 'required|integer|min:1',
            'convert_to_order' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => __('messages.pre_order.client_id_required'),
            'client_id.exists' => __('messages.pre_order.client_id_exists'),

            'address_id.required' => __('messages.pre_order.address_id_required'),
            'address_id.exists' => __('messages.pre_order.address_id_exists'),

            'coupon_code.string' => __('messages.pre_order.coupon_code_string'),
            'coupon_code.exists' => __('messages.pre_order.coupon_code_exists'),

            'notes.string' => __('messages.pre_order.notes_string'),
            'notes.max' => __('messages.pre_order.notes_max'),

            'payment_method.required' => __('messages.pre_order.payment_method_required'),
            'payment_method.integer' => __('messages.pre_order.payment_method_integer'),
            'payment_method.between' => __('messages.pre_order.payment_method_between'),

            'payment_status.required' => __('messages.pre_order.payment_status_required'),
            'payment_status.integer' => __('messages.pre_order.payment_status_integer'),
            'payment_status.between' => __('messages.pre_order.payment_status_between'),

            'products.required' => __('messages.pre_order.products_required'),
            'products.array' => __('messages.pre_order.products_array'),
            'products.min' => __('messages.pre_order.products_min'),

            'products.*.variant_id.required' => __('messages.pre_order.variant_id_required'),
            'products.*.variant_id.exists' => __('messages.pre_order.variant_id_exists'),

            'products.*.quantity.required' => __('messages.pre_order.quantity_required'),
            'products.*.quantity.integer' => __('messages.pre_order.quantity_integer'),
            'products.*.quantity.min' => __('messages.pre_order.quantity_min'),

            'convert_to_order.boolean' => __('messages.pre_order.convert_to_order_boolean'),
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
