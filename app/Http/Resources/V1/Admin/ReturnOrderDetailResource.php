<?php

namespace App\Http\Resources\V1\Admin;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnOrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
       $stockAdjustment = $this->stockAdjustments->first();


        return [
            'id' => $this->id,
            'variant_id' => $this->variant_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'refund_amount' => $this->refund_amount,
            'total' => $this->total,
            'variant' => new VariantResource($this->whenLoaded('variant')),
            'product' => new ProductResource(optional($this->variant)->product),
             'warehouse' => $stockAdjustment ? new WarehouseResource($stockAdjustment->warehouse) : null,
            'shelf' => $stockAdjustment ? new ShelfResource($stockAdjustment->shelf) : null,
        ];
    }
}
