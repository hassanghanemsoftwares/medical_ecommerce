<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class OrderRequest extends FormRequest
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
            'coupon_code' => 'nullable|exists:coupons,code',
            'notes' => 'nullable|string|max:255',
            'payment_method' => 'required|integer|between:0,0',
            'payment_status' => 'required|integer|between:0,3',
            'products' => 'required|array|min:1',
            'products.*.variant_id' => 'required|exists:variants,id',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => __('messages.order.client_id_required'),
            'client_id.exists' => __('messages.order.client_id_exists'),

            'address_id.required' => __('messages.order.address_id_required'),
            'address_id.exists' => __('messages.order.address_id_exists'),

            'coupon_code.exists' => __('messages.order.coupon_code_exists'),

            'notes.string' => __('messages.order.notes_string'),
            'notes.max' => __('messages.order.notes_max'),

            'payment_method.required' => __('messages.order.payment_method_required'),
            'payment_method.integer' => __('messages.order.payment_method_integer'),
            'payment_method.between' => __('messages.order.payment_method_between'),

            'payment_status.required' => __('messages.order.payment_status_required'),
            'payment_status.integer' => __('messages.order.payment_status_integer'),
            'payment_status.between' => __('messages.order.payment_status_between'),

            'products.required' => __('messages.order.products_required'),
            'products.array' => __('messages.order.products_array'),
            'products.min' => __('messages.order.products_min'),

            'products.*.variant_id.required' => __('messages.order.variant_id_required'),
            'products.*.variant_id.exists' => __('messages.order.variant_id_exists'),

            'products.*.quantity.required' => __('messages.order.quantity_required'),
            'products.*.quantity.integer' => __('messages.order.quantity_integer'),
            'products.*.quantity.min' => __('messages.order.quantity_min'),
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
