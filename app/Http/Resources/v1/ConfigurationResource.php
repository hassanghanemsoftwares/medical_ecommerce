<?php
namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
        ];
    }
}
