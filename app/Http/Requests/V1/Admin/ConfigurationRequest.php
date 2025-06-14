<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme_color1' => 'nullable|string|max:7', // e.g. #324057
            'theme_color2' => 'nullable|string|max:7', // e.g. #EEABAD
            'delivery_charge' => 'nullable|numeric|min:0',
            'min_stock_alert' => 'nullable|integer|min:1',
            'store_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'store_address' => 'nullable|string|max:255',

            'about_us.title.en' => 'nullable|string|max:255',
            'about_us.title.ar' => 'nullable|string|max:255',
            'about_us.description.en' => 'nullable|string',
            'about_us.description.ar' => 'nullable|string',
            'about_us.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'theme_color1.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.theme_color1')]),
            'theme_color1.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.theme_color1'), 'max' => 7]),

            'theme_color2.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.theme_color2')]),
            'theme_color2.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.theme_color2'), 'max' => 7]),

            'delivery_charge.numeric' => __('messages.validation.numeric', ['attribute' => __('messages.validation.attributes.delivery_charge')]),
            'delivery_charge.min' => __('messages.validation.min.numeric', ['attribute' => __('messages.validation.attributes.delivery_charge'), 'min' => 0]),

            'min_stock_alert.integer' => __('messages.validation.integer', ['attribute' => __('messages.validation.attributes.min_stock_alert')]),
            'min_stock_alert.min' => __('messages.validation.min.numeric', ['attribute' => __('messages.validation.attributes.min_stock_alert'), 'min' => 1]),

            'store_name.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.store_name')]),
            'store_name.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.store_name'), 'max' => 255]),

            'contact_email.email' => __('messages.validation.email', ['attribute' => __('messages.validation.attributes.contact_email')]),
            'contact_email.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.contact_email'), 'max' => 255]),

            'contact_phone.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.contact_phone')]),
            'contact_phone.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.contact_phone'), 'max' => 20]),

            'store_address.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.store_address')]),
            'store_address.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.store_address'), 'max' => 255]),

            'about_us.title.en.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.about_us_title_en')]),
            'about_us.title.en.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.about_us_title_en'), 'max' => 255]),
            'about_us.title.ar.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.about_us_title_ar')]),
            'about_us.title.ar.max' => __('messages.validation.max.string', ['attribute' => __('messages.validation.attributes.about_us_title_ar'), 'max' => 255]),

            'about_us.description.en.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.about_us_description_en')]),
            'about_us.description.ar.string' => __('messages.validation.string', ['attribute' => __('messages.validation.attributes.about_us_description_ar')]),

            'about_us.image.image' => __('messages.validation.image', ['attribute' => __('messages.validation.attributes.about_us_image')]),
            'about_us.image.mimes' => __('messages.validation.mimes', ['attribute' => __('messages.validation.attributes.about_us_image')]),
            'about_us.image.max' => __('messages.validation.max.file', ['attribute' => __('messages.validation.attributes.about_us_image'), 'max' => 2048]),
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
