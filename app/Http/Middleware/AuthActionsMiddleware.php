<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Team;
use Spatie\Permission\Models\Role;

class AuthActionsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // $adminRole = Role::where('name', 'admin')->where('team_id', 2)->first();
        // setPermissionsTeamId(2);
        // $user->assignRole($adminRole);
        $teamIdFromHeader = $request->header('X-Team-ID');
        if (!$user || !$teamIdFromHeader) {
            return response()->json(['result' => false,   'message' => __('messages.auth.missing_user_or_team'),], 401);
        }
        $team = Team::find($teamIdFromHeader);
        if (!$team) {
            return response()->json(['result' => false, 'message' => __('messages.auth.team_not_found'),], 404);
        }

        if (!$user->hasAccessToTeam($teamIdFromHeader)) {
            return response()->json(['result' => false,   'message' => __('messages.auth.user_not_in_team'),], 403);
        }
        setPermissionsTeamId($teamIdFromHeader);
        return $next($request);
    }
}
