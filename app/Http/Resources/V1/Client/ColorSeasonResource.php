<?php
namespace App\Http\Resources\V1\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class ColorSeasonResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
        ];
    }
}
