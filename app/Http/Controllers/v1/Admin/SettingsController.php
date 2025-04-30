<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{

    public function index(): JsonResponse
    {
        try {
            $permissions = Permission::select('id', 'name')
                ->whereNotIn('name', ['view-activity-logs'])
                ->orderBy('id', 'asc')
                ->get();
            $roles = Role::where('team_id', getPermissionsTeamId())
                ->select('id', 'name')
                ->get();

            return response()->json([
                'result' => true,
                'message' => __('messages.retrieved_successfully'),
                'permissions' => $permissions,
                'roles' => $roles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __('messages.failed_to_retrieve_data'),
                'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error')
            ]);
        }
    }
}
