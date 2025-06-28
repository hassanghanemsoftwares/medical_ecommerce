<?php

namespace App\Http\Resources\V1\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'status' => $this->status['key']??"0",
            'status_attributes' => $this->status,
            'coupon_type' => $this->coupon_type,
            'coupon_type_attributes' =>$this->getAllCouponTypes($this->coupon_type) ,
            'valid_from' => $this->valid_from?->toDateString(),
            'valid_to' => $this->valid_to?->toDateString(),
        ];
    }
}
