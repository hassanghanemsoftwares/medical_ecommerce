<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    public function toArray($request): array
    {
        $variant = $this->whenLoaded('variant');

        $productName = optional($variant->product)->name ?? __('messages.common.no_product');
        $colorName = optional($variant->color)->name ?? __('messages.common.no_color');
        $sizeName = optional($variant->size)->name ?? __('messages.common.no_size');

        $locale = app()->getLocale();

        $productName = is_array($productName) ? ($productName[$locale] ?? reset($productName)) : $productName;
        $colorName = is_array($colorName) ? ($colorName[$locale] ?? reset($colorName)) : $colorName;
        $sizeName = is_array($sizeName) ? ($sizeName[$locale] ?? reset($sizeName)) : $sizeName;

        return [
            'id' => $this->id,

            'product_info' => "{$productName} {$colorName} {$sizeName}",
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'shelf' => new ShelfResource($this->whenLoaded('shelf')),
            'quantity' => $this->quantity,
            'created_at' => $this->created_at,
        ];
    }
}
