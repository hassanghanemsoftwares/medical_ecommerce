<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'country' => $this->country,
            'city' => $this->city,
            'district' => $this->district,
            'governorate' => $this->governorate,
            'specifications' => $this->specifications,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            // Optionally, include related client data if loaded
            'client' => new ClientResource($this->whenLoaded('client')),
        ];
    }
}
