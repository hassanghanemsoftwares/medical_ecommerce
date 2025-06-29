<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\TeamMemberResource;
use App\Models\TeamMember;
use Exception;

class ClientTeamMemberController extends Controller
{
    public function index()
    {
        try {
            $teamMembers = TeamMember::where("is_active", true)->orderBy("arrangement", "asc")->get();
            return response()->json([
                'result' => true,
                'message' => __('messages.team_member.team_members_retrieved'),
                'team_members' => TeamMemberResource::collection($teamMembers),
            ]);
        } catch (Exception $e) {
           return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
