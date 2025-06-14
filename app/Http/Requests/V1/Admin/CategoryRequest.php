<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $categoryId = $this->route('category');

        $rules = [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name->en,' . ($categoryId ?? 'NULL')
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name->ar,' . ($categoryId ?? 'NULL')
            ],
            'arrangement' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];

        $imageRule = [
            $this->isMethod('post') ? 'required' : 'nullable',
            'image',
            'mimes:jpeg,jpg,png,gif',
            'max:2048',
            'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ];

        $rules['image'] = $imageRule;

        return $rules;
    }

    public function messages()
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

            'image.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.image')]),
            'image.image' => __('messages.validation.image', ['attribute' => __('messages.validation.attributes.image')]),
            'image.mimes' => __('messages.validation.mimes', ['attribute' => __('messages.validation.attributes.image')]),
            'image.max' => __('messages.validation.max.file', ['attribute' => __('messages.validation.attributes.image'), 'max' => 2048]),
            'image.dimensions' => __('messages.validation.dimensions', ['attribute' => __('messages.validation.attributes.image')]),

            'arrangement.integer' => __('messages.validation.integer', ['attribute' => __('messages.validation.attributes.arrangement')]),
            'arrangement.min' => __('messages.validation.min.numeric', ['attribute' => __('messages.validation.attributes.arrangement'), 'min' => 0]),

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
