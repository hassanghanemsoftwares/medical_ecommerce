<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'size' => new SizeResource($this->whenLoaded('size')),
            'color' => new ColorResource($this->whenLoaded('color')),
        ];
    }
}
