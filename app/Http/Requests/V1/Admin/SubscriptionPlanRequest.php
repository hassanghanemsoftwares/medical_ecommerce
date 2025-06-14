<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class SubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subscriptionPlanId = $this->route('subscription_plan');
        return [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:191',
                'unique:subscription_plans,name->en,' . ($subscriptionPlanId ? $subscriptionPlanId : 'NULL')
            ],
            'name.ar' => [
                'required',
                'string',
                'max:191',
                'unique:subscription_plans,name->ar,' . ($subscriptionPlanId ? $subscriptionPlanId : 'NULL')
            ],
            'price' => 'required|numeric|min:0',
            'duration_in_days' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.subscription_plan.name_required'),
            'name.en.required' => __('messages.subscription_plan.name_en_required'),
            'name.en.unique' => __('messages.subscription_plan.name_en_unique'),
            'name.ar.required' => __('messages.subscription_plan.name_ar_required'),
            'name.ar.unique' => __('messages.subscription_plan.name_ar_unique'),
            'price.required' => __('messages.subscription_plan.price_required'),
            'price.numeric' => __('messages.subscription_plan.price_numeric'),
            'price.min' => __('messages.subscription_plan.price_min'),
            'duration_in_days.required' => __('messages.subscription_plan.duration_required'),
            'duration_in_days.integer' => __('messages.subscription_plan.duration_integer'),
            'duration_in_days.min' => __('messages.subscription_plan.duration_min'),
            'is_active.boolean' => __('messages.subscription_plan.is_active_boolean'),
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
