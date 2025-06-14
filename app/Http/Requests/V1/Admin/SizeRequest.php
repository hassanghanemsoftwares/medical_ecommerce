<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class SizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sizeId = $this->route('size');

        return [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name->en,' . ($sizeId ? $sizeId : 'NULL')
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name->ar,' . ($sizeId ? $sizeId : 'NULL')
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.size.name_required'),
            'name.en.required' => __('messages.size.name_en_required'),
            'name.en.string' => __('messages.size.name_en_string'),
            'name.en.max' => __('messages.size.name_en_max'),
            'name.en.unique' => __('messages.size.name_en_unique'),
            'name.ar.required' => __('messages.size.name_ar_required'),
            'name.ar.string' => __('messages.size.name_ar_string'),
            'name.ar.max' => __('messages.size.name_ar_max'),
            'name.ar.unique' => __('messages.size.name_ar_unique'),
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
