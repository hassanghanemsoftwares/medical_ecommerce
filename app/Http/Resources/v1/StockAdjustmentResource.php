<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
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

            'variant' => new VariantResource($this->whenLoaded('variant')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'shelf' => new ShelfResource($this->whenLoaded('shelf')),
            'type'          => $this->type,
            'quantity'      => $this->quantity,
            'cost_per_item' => $this->cost_per_item !== null ? number_format($this->cost_per_item, 2) : null,
            'reason'        => $this->reason,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
           'adjusted_by' => new UserResource($this->whenLoaded('adjustedBy')),

            'created_at' => $this->created_at,
        ];
    }
}
