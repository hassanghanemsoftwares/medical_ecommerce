<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UserRequest;
use App\Http\Resources\V1\PaginationResource;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\V1\UserResource as V1UserResource;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'role' => 'nullable|string|max:100',
                'sort' => 'nullable|in:created_at,name,email,last_activity,role',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $sortBy = $validated['sort'] ?? 'created_at';
            $sortDirection = $validated['order'] ?? 'desc';
            $perPage = $validated['per_page'] ?? 10;
            $teamId = getPermissionsTeamId();

            $query = User::query()
                ->with('roles')
                ->whereNotIn('id', [1, 2, $request->user()->id])
                ->whereHas('roles', fn($q) => $q->where('model_has_roles.team_id', $teamId));

            if (!empty($validated['search'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('name', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('email', 'like', '%' . $validated['search'] . '%')
                        ->orWhereHas('roles', fn($q) => $q->where('name', 'like', '%' . $validated['search'] . '%'));
                });
            }

            if (!empty($validated['role'])) {
                $query->whereHas('roles', fn($q) => $q->where('name', $validated['role']));
            }

            if ($sortBy === 'last_activity') {
                $query->addSelect([
                    'last_activity' => DB::table('sessions')
                        ->selectRaw('MAX(last_activity)')
                        ->whereColumn('user_id', 'users.id')
                ])->orderBy('last_activity', $sortDirection);
            } elseif ($sortBy === 'role') {
                $query->addSelect([
                    'role_name' => DB::table('model_has_roles')
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->select('roles.name')
                        ->whereColumn('model_has_roles.model_id', 'users.id')
                        ->orderBy('roles.name')
                        ->limit(1)
                ])->orderBy('role_name', $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            $users = $query->paginate($perPage);

            if ($sortBy !== 'last_activity') {
                $userIds = $users->pluck('id');
                $activities = DB::table('sessions')
                    ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
                    ->whereIn('user_id', $userIds)
                    ->groupBy('user_id')
                    ->pluck('last_activity', 'user_id');

                $users->getCollection()->transform(function ($user) use ($activities) {
                    $lastActivity = $activities[$user->id] ?? null;
                    $user->last_activity = $lastActivity ? Carbon::createFromTimestamp($lastActivity) : null;
                    return $user;
                });
            }

            return response()->json([
                'result' => true,
                'message' => __('messages.user.users_retrieved'),
                'users' => V1UserResource::collection($users),
                'pagination' => new PaginationResource($users),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __('messages.user.failed_to_retrieve_users'),
                'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
            ]);
        }
    }
    public function show(User $user)
    {
        try {
            return response()->json([
                'result' => true,
                'message' => __('messages.user.user_found'),
                'user' => new V1UserResource($user),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __('messages.user.failed_to_retrieve_user'),
                'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
            ]);
        }
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();

            return response()->json([
                'result' => true,
                'message' => __('messages.user.user_deleted'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __('messages.user.failed_to_delete_user'),
                'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
            ]);
        }
    }

    public function create(UserRequest $request)
    {
        try {

            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            $user->syncRoles([$request->role]);

            if (!empty($request->permissions)) {
                $user->syncPermissions($request->permissions);
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.user.user_created'),
                'user' => new V1UserResource($user),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'result' => false,
                'message' => __('messages.user.failed_to_create_user'),
                'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
            ]);
        }
    }
    public function update(UserRequest $request, $id)
    {
        try {

            DB::beginTransaction();

            $user = User::findOrFail($id);
            $user->name = $request->input('name', $user->name);
            $user->email = $request->input('email', $user->email);
            if ($request->has('is_active')) {
                $user->is_active = $request->boolean('is_active');
            }
            $user->save();

            if ($request->filled('role')) {
                $user->syncRoles([$request->role]);
            }

            if ($request->has('permissions')) {
                $user->syncPermissions($request->permissions);
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.user.user_updated'),
                'user' => new V1UserResource($user),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.user.failed_to_update_user', $e);

        }
    }
}
