<?php
namespace App\Http\Requests\v1;

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
        $categoryId = $this->route('category'); // Get the category ID from the route (for update requests)

        $rules = [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name->en,' . ($categoryId ? $categoryId : 'NULL') // Check uniqueness while excluding the current category ID for update
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name->ar,' . ($categoryId ? $categoryId : 'NULL') // Check uniqueness while excluding the current category ID for update
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
            'name.required' => __('messages.category.name_required'),
            'name.en.required' => __('messages.category.name_en_required'),
            'name.en.unique' => __('messages.category.name_en_unique'), // Custom error message for unique validation
            'name.ar.required' => __('messages.category.name_ar_required'),
            'name.ar.unique' => __('messages.category.name_ar_unique'), // Custom error message for unique validation
            'image.required' => __('messages.category.image_required'),
            'image.mimes' => __('messages.category.image_mimes'),
            'image.max' => __('messages.category.image_max_size'),
            'image.dimensions' => __('messages.category.image_dimensions'),
        ];
    }

    // Override the failedValidation method
    protected function failedValidation(Validator $validator)
    {
        // Instead of throwing the default ValidationException, 
        // you can return a custom response or prevent the 422 error
        // For example, you could return a custom response with a success flag and the validation errors
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
            'request_data' => $this->all()
        ], 200)); // You can return any other status code if needed
    }
}
