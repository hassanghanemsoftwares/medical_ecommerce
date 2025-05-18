<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'short_description' => $this->getTranslations('short_description'),
            'description' => $this->getTranslations('description'),
            'barcode' => $this->barcode,
            'slug' => $this->slug,
            'availability_status' => $this->availability_status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'price' => $this->price,
            'discount' => $this->discount,
            'min_order_quantity' => $this->min_order_quantity,
            'max_order_quantity' => $this->max_order_quantity,
            'image' => $this->images->first()->image,
            'images' => ProductImageResource::collection($this->images),
            'variants' => VariantResource::collection($this->variants),
            'tags' => TagResource::collection($this->tags),
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,

        ];
    }
}
