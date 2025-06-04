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
                'unique:categories,name->en,' . ($categoryId ? $categoryId : 'NULL') 
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name->ar,' . ($categoryId ? $categoryId : 'NULL') 
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
            'name.en.unique' => __('messages.category.name_en_unique'), 
            'name.ar.required' => __('messages.category.name_ar_required'),
            'name.ar.unique' => __('messages.category.name_ar_unique'), 
            'image.required' => __('messages.category.image_required'),
            'image.mimes' => __('messages.category.image_mimes'),
            'image.max' => __('messages.category.image_max_size'),
            'image.dimensions' => __('messages.category.image_dimensions'),
        ];
    }

    
    protected function failedValidation(Validator $validator)
    {
        
        
        
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
            'request_data' => $this->all()
        ], 200)); 
    }
}
