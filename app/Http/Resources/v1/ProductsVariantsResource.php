<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsVariantsResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $productName = optional($this->product)->name ?? __('messages.common.no_product');
        $colorName = optional($this->color)->name ?? __('messages.common.no_color');
        $sizeName = optional($this->size)->name ?? __('messages.common.no_size');

        $locale = app()->getLocale();

        $productName = is_array($productName) ? ($productName[$locale] ?? reset($productName)) : $productName;
        $colorName = is_array($colorName) ? ($colorName[$locale] ?? reset($colorName)) : $colorName;
        $sizeName = is_array($sizeName) ? ($sizeName[$locale] ?? reset($sizeName)) : $sizeName;
        return [
            'id' => $this->id,
            'product_info' => "{$productName} {$colorName} {$sizeName}",
            'product' => new ProductResource($this->product),
            'size' => $this->size ? new SizeResource($this->size) : null,
            'color' => $this->color ? new ColorResource($this->color) : null,
        ];
    }
}
