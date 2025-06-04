<?php

namespace App\Http\Resources\V1\Client;
use Illuminate\Http\Resources\Json\JsonResource;

class OccupationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
