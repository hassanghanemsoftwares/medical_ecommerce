<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class HomeSectionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'type' => 'required|string|max:50',
            'title' => 'required|array',
            'title.en' => 'required|string|max:255',
            'title.ar' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'arrangement' => 'nullable|integer|min:1',

            'banners' => 'nullable|array',
            'banners.*.link' => 'nullable|url|max:255',
            'banners.*.title' => 'nullable|array',
            'banners.*.title.en' => 'nullable|string|max:255',
            'banners.*.title.ar' => 'nullable|string|max:255',
            'banners.*.subtitle' => 'nullable|array',
            'banners.*.subtitle.en' => 'nullable|string|max:255',
            'banners.*.subtitle.ar' => 'nullable|string|max:255',
            'banners.*.arrangement' => 'nullable|integer|min:1',
            'banners.*.is_active' => 'nullable|boolean',

            'product_section_items' => 'nullable|array',
            'product_section_items.*.product_id' => 'required|integer|exists:products,id',
            'product_section_items.*.arrangement' => 'nullable|integer|min:1',
            'product_section_items.*.is_active' => 'nullable|boolean',
        ];

        $imageRule = [
            $this->isMethod('post') ? 'required' : 'nullable',
            'image',
            'mimes:jpeg,jpg,png,gif',
            'max:2048',
            'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ];

        $rules['banners.*.image'] = $imageRule;

        return $rules;
    }

    public function messages()
    {
        return [
            'type.required' => __('messages.home_section.type_required'),
            'title.required' => __('messages.home_section.title_required'),
            'title.en.required' => __('messages.home_section.title_en_required'),
            'title.ar.required' => __('messages.home_section.title_ar_required'),

            'banners.*.title.required' => __('messages.home_section.banner_title_required'),
            'banners.*.subtitle.required' => __('messages.home_section.banner_subtitle_required'),

            'image.required' => __('messages.home_section.image_required'),
            'image.mimes' => __('messages.home_section.image_mimes'),
            'image.max' => __('messages.home_section.image_max_size'),
            'image.dimensions' => __('messages.home_section.image_dimensions'),

            'banners.*.image.required' => __('messages.home_section.banner_image_required'),
            'banners.*.image.mimes' => __('messages.home_section.banner_image_mimes'),
            'banners.*.image.max' => __('messages.home_section.banner_image_max_size'),
            'banners.*.image.dimensions' => __('messages.home_section.banner_image_dimensions'),
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
