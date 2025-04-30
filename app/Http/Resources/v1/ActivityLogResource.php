<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->getLocalizedLogName(),
            'description' => $this->getLocalizedDescription(),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'causer_name' => optional($this->causer)->name,
            'properties' => $this->properties,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
    protected function getLocalizedDescription(): string
    {

        $fullKey = 'activity.' . $this->description;
        $translated = __($fullKey);

        if (preg_match('/^(\w+)\.(created|updated|deleted)$/', $this->description, $matches)) {
            $modelKey = $matches[1];
            $event = $matches[2];

            $modelName = __('activity.models.' . $modelKey);
            $template = __('activity.default.' . $event);

            return str_replace(':model', $modelName, $template);
        }

        return $translated;
    }
    public function getLocalizedLogName(): string
    {
        $key = 'activity.models.' . $this->log_name;
        $translated = __($key);

        return $translated !== $key ? $translated : $this->log_name;
    }
}
