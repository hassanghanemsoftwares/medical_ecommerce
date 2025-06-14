<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class LearningVideoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTranslations('title'),
            'description' => $this->getTranslations('description'),
            'video' => $this->video,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
