<?php

namespace App\Http\Requests\V1;

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
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
            'request_data' => $this->all()
        ], 200));
    }
}
