<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $addressId = $this->route('address');

        return [
            'client_id' => 'required|exists:clients,id',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'district' => 'nullable|string|max:100',
            'governorate' => 'nullable|string|max:100',
            'specifications' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.client_id')]),
            'client_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.validation.attributes.client_id')]),
            'country.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.country')]),
            'country.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.country')]),
            'country.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.country'), 'max' => 100]),
            'city.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.city')]),
            'city.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.city')]),
            'city.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.city'), 'max' => 100]),
            'district.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.district')]),
            'district.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.district'), 'max' => 100]),
            'governorate.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.governorate')]),
            'governorate.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.governorate'), 'max' => 100]),
            'specifications.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.specifications')]),
            'specifications.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.specifications'), 'max' => 255]),
            'latitude.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.validation.attributes.latitude')]),
            'latitude.between' => __('messages.validation.between.numeric', ['attribute' => __('messages.validation.attributes.latitude'), 'min' => -90, 'max' => 90]),
            'longitude.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.validation.attributes.longitude')]),
            'longitude.between' => __('messages.validation.between.numeric', ['attribute' => __('messages.validation.attributes.longitude'), 'min' => -180, 'max' => 180]),
            'is_active.boolean' => __('messages.validation.boolean', ['attribute' => __('messages.validation.attributes.is_active')]),
            'is_default.boolean' => __('messages.validation.boolean', ['attribute' => __('messages.validation.attributes.is_default')]),
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
