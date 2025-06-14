<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon');

        $rules = [
            'code' => 'required|string|max:255|unique:coupons,code,' . ($couponId ?? 'NULL'),
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'coupon_type' => 'required|integer|between:0,4',
            'client_id' => 'nullable|exists:clients,id',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ];

        // Add status validation only on update/edit
        if ($couponId) {
            $rules['status'] = 'required|integer|in:0,1,2,5';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'code.required' => __('messages.coupon.code_required'),
            'code.unique' => __('messages.coupon.code_unique'),
            'status.required' => __('messages.coupon.status_required'),
            'status.in' => __('messages.coupon.status_invalid'),

            'type.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.type')]),
            'type.in' => __('messages.validation.in', ['attribute' => __('messages.validation.attributes.type')]),

            'value.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.value')]),
            'value.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.validation.attributes.value')]),
            'value.min' => __('messages.validation.min.numeric', ['attribute' => __('messages.validation.attributes.value'), 'min' => 0]),

            'usage_limit.integer' => __('messages.validation.integer', ['attribute' => __('messages.validation.attributes.usage_limit')]),
            'usage_limit.min' => __('messages.validation.min.numeric', ['attribute' => __('messages.validation.attributes.usage_limit'), 'min' => 1]),

            'min_order_amount.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.validation.attributes.min_order_amount')]),
            'min_order_amount.min' => __('messages.validation.min.numeric', ['attribute' => __('messages.validation.attributes.min_order_amount'), 'min' => 0]),

            'coupon_type.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.coupon_type')]),
            'coupon_type.integer' => __('messages.validation.integer', ['attribute' => __('messages.validation.attributes.coupon_type')]),
            'coupon_type.between' => __('messages.validation.between.numeric', ['attribute' => __('messages.validation.attributes.coupon_type'), 'min' => 0, 'max' => 4]),

            'client_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.validation.attributes.client_id')]),

            'valid_from.date' => __('messages.validation.date', ['attribute' => __('messages.validation.attributes.valid_from')]),
            'valid_to.date' => __('messages.validation.date', ['attribute' => __('messages.validation.attributes.valid_to')]),
            'valid_to.after_or_equal' => __('messages.validation.after_or_equal', ['attribute' => __('messages.validation.attributes.valid_to'), 'date' => __('messages.validation.attributes.valid_from')]),
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
