<?php

namespace App\Http\Resources\V1\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SortResource extends JsonResource
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
            'key' =>$this['key'],
            'label' => [
                'en' => $this['en'],
                'ar' => $this['ar'],
            ],
        ];
    }
}
