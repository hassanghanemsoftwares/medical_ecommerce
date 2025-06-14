<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $colorId = $this->route('color');

        return [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:255',
                'unique:colors,name->en,' . ($colorId ?? 'NULL'),
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:colors,name->ar,' . ($colorId ?? 'NULL'),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                'unique:colors,code,' . ($colorId ?? 'NULL'),
            ],
            'color_season_id' => [
                'required',
                'exists:color_seasons,id',
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

            'code.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.code')]),
            'code.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.code')]),
            'code.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.code'), 'max' => 20]),
            'code.unique' => __('messages.validation.unique', ['attribute' => __('messages.validation.attributes.code')]),

            'color_season_id.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.color_season')]),
            'color_season_id.exists' => __('messages.validation.exists', ['attribute' => __('messages.validation.attributes.color_season')]),
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
