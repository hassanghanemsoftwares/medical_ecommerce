<?php
namespace App\Http\Resources\V1\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ColorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'code' => $this->code,
            'color_season_id' => $this->color_season_id,
            'color_season' => new ColorSeasonResource($this->whenLoaded('colorSeason')),
        ];
    }
}
