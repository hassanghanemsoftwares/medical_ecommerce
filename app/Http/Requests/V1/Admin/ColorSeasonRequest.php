<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ColorSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $colorSeasonId = $this->route('color_season');

        return [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:255',
                'unique:color_seasons,name->en,' . ($colorSeasonId ?? 'NULL'),
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:color_seasons,name->ar,' . ($colorSeasonId ?? 'NULL'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.name')]),

            'name.en.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.name_en')]),
            'name.en.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.name_en')]),
            'name.en.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.name_en'), 'max' => 255]),
            'name.en.unique' => __('messages.validation.unique', ['attribute' => __('messages.validation.attributes.name_en')]),

            'name.ar.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.name_ar')]),
            'name.ar.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.name_ar')]),
            'name.ar.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.name_ar'), 'max' => 255]),
            'name.ar.unique' => __('messages.validation.unique', ['attribute' => __('messages.validation.attributes.name_ar')]),
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
