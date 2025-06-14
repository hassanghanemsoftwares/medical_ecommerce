<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class OccupationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $occupationId = $this->route('occupation');

        return [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:255',
                'unique:occupations,name->en,' . ($occupationId ?: 'NULL'),
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:occupations,name->ar,' . ($occupationId ?: 'NULL'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.occupation.name_required'),
            'name.en.required' => __('messages.occupation.name_en_required'),
            'name.en.unique' => __('messages.occupation.name_en_unique'),
            'name.en.max' => __('messages.occupation.name_en_max'),
            'name.ar.required' => __('messages.occupation.name_ar_required'),
            'name.ar.unique' => __('messages.occupation.name_ar_unique'),
            'name.ar.max' => __('messages.occupation.name_ar_max'),
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
