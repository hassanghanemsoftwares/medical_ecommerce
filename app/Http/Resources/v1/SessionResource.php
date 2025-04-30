<?php

namespace App\Http\Resources\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class SessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $agent = new Agent();
        $agent->setUserAgent($this->user_agent);
        // $location = Location::get($this->ip_address);
        $location = Location::get('91.232.100.54');
   
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'last_activity' => $this->last_activity,
            'last_activity_human' => Carbon::createFromTimestamp($this->last_activity)->diffForHumans(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device' => $agent->device(),
            'is_mobile' => $agent->isMobile(),
            'is_tablet' => $agent->isTablet(),
            'is_desktop' => $agent->isDesktop(),
            'is_robot' => $agent->isRobot(),
            'is_current_device' =>  $this->isCurrentSession(),

            'location'   => ($location->countryName ?? 'Unknown') . "-" . ($location->cityName ?? 'Unknown'),
        ];
    }
}
