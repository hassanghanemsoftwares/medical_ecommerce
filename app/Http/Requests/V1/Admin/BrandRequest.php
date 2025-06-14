<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class BrandRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $brandId = $this->route('brand');

        return [
            'name' => 'required|string|max:255|unique:brands,name,' . ($brandId ?? 'NULL'),
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.name')]),
            'name.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.name')]),
            'name.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.name'), 'max' => 255]),
            'name.unique' => __('messages.validation.unique', ['attribute' => __('messages.validation.attributes.name')]),
            'is_active.boolean' => __('messages.validation.boolean', ['attribute' => __('messages.validation.attributes.is_active')]),
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
