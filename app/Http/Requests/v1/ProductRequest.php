<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $productId = $this->route('product');

        return [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:191',
                'unique:products,name->en,' . ($productId ? $productId : 'NULL')
            ],
            'name.ar' => [
                'required',
                'string',
                'max:191',
                'unique:products,name->ar,' . ($productId ? $productId : 'NULL')
            ],
            'short_description' => 'required|array',
            'short_description.en' => [
                'required',
                'string',
                'max:191',
            ],
            'short_description.ar' => [
                'required',
                'string',
                'max:191',
            ],
            'description' => 'required|array',
            'description.en' => [
                'required',
                'string',
            ],
            'description.ar' => [
                'required',
                'string',
            ],
            'barcode' => 'required|string|unique:products,barcode,' . ($productId ? $productId : 'NULL'),
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'availability_status' => 'required|in:available,coming_soon,discontinued,pre_order',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|integer|min:0|max:100',
            'min_order_quantity' => 'required|integer|min:1',
            'max_order_quantity' => 'nullable|integer|min:0',
            'images' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'array',
            ],
            'images.*.image' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,gif',
                'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
            'images.*.is_active' => 'required|boolean',
            'images.*.arrangement' => 'required|integer|min:1',
            'tags' => 'required|array',
            'tags.*' => 'required|exists:tags,id',
            'variants' => ['nullable', 'array', function ($attribute, $value, $fail) {
                $unique = [];

                foreach ($value as $index => $variant) {
                    $key = $variant['size_id'] . '-' . $variant['color_id'];
                    if (in_array($key, $unique)) {
                        return $fail(__('messages.product.duplicate_variant', ['index' => $index + 1]));
                    }
                    $unique[] = $key;
                }
            }],
            'variants.*.size_id' => 'required|exists:sizes,id',
            'variants.*.color_id' => 'required|exists:colors,id',
         
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('messages.product.name_required'),
            'name.en.required' => __('messages.product.name_en_required'),
            'name.en.unique' => __('messages.product.name_en_unique'),
            'name.ar.required' => __('messages.product.name_ar_required'),
            'name.ar.unique' => __('messages.product.name_ar_unique'),
            'barcode.required' => __('messages.product.barcode_required'),
            'barcode.unique' => __('messages.product.barcode_unique'),
            'category_id.required' => __('messages.product.category_required'),
            'category_id.exists' => __('messages.product.category_exists'),
            'brand_id.required' => __('messages.product.brand_required'),
            'brand_id.exists' => __('messages.product.brand_exists'),
            'price.required' => __('messages.product.price_required'),
            'price.numeric' => __('messages.product.price_numeric'),
            'price.min' => __('messages.product.price_min'),
            'discount.integer' => __('messages.product.discount_integer'),
            'min_order_quantity.required' => __('messages.product.min_order_quantity_required'),
            'min_order_quantity.integer' => __('messages.product.min_order_quantity_integer'),
            'min_order_quantity.min' => __('messages.product.min_order_quantity_min'),
            'max_order_quantity.integer' => __('messages.product.max_order_quantity_integer'),
            'images.required' => __('messages.product.image_required'),
            'images.mimes' => __('messages.product.image_mimes'),
            'images.max' => __('messages.product.image_max_size'),
            'images.dimensions' => __('messages.product.image_dimensions'),
            'variants.*.size_id.required' => __('messages.variant.size_required'),
            'variants.*.size_id.exists' => __('messages.variant.size_exists'),
            'variants.*.color_id.required' => __('messages.variant.color_required'),
            'variants.*.color_id.exists' => __('messages.variant.color_exists'),
            'duplicate_variant' => 'Duplicate variant found at row #:index (same size and color).',
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
