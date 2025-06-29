<?php

namespace App\Http\Requests\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

class WishlistItemRequest extends FormRequest
{
    public function authorize()
    {
        return true; // You can add auth logic if needed
    }

    public function rules()
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
        ];
    }
}
