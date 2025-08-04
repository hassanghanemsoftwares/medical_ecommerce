<?php

namespace App\Http\Resources\V1\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'subscription_plan' => new SubscriptionPlanResource($this->whenLoaded('subscription_plan')),
            'payment_gateway_type' => $this->payment_gateway_type,
            'payment_gateway_id' => $this->payment_gateway_id,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'is_active' => $this->isActive(),
        ];
    }
}
