<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsVariantsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_info' => $this->display_sku ?? '',
            'product' => new ProductResource($this->product),
            'size' => $this->size ? new SizeResource($this->size) : null,
            'color' => $this->color ? new ColorResource($this->color) : null,
        ];
    }
}
