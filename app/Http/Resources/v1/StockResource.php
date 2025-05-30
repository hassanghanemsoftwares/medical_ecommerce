<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    public function toArray($request): array
    {
        $variant = $this->whenLoaded('variant');

        return [
            'id' => $this->id,
            'product_info' => $variant?->display_sku ?? '',
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'shelf' => new ShelfResource($this->whenLoaded('shelf')),
            'quantity' => $this->quantity,
            'created_at' => $this->created_at,
        ];
    }
}
