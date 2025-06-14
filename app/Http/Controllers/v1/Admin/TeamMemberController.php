<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\TeamMemberRequest;
use App\Http\Resources\V1\Admin\TeamMemberResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name,occupation,arrangement,is_active',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $teamMembers = TeamMember::query()
                ->when(
                    $validated['search'] ?? null,
                    fn($q, $search) =>
                        $q->where('name->en', 'like', "%$search%")
                          ->orWhere('name->ar', 'like', "%$search%")
                          ->orWhere('occupation->en', 'like', "%$search%")
                          ->orWhere('occupation->ar', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.team_member.team_members_retrieved'),
                'team_members' => TeamMemberResource::collection($teamMembers),
                'pagination' => new PaginationResource($teamMembers),
            ]);
        } catch (Exception $e) {
             return $this->errorResponse(__('messages.team_member.failed_to_retrieve_data'), $e);
        }
    }

    public function show(TeamMember $teamMember)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.team_member.team_member_found'),
            'team_member' => new TeamMemberResource($teamMember),
        ]);
    }

    public function store(TeamMemberRequest $request)
    {
        try {
            DB::beginTransaction();

            $teamMember = new TeamMember([
                'name' => $request->input('name'),
                'occupation' => $request->input('occupation'),
                'is_active' => $request->boolean('is_active', true),
                'arrangement' => TeamMember::getNextArrangement(),
            ]);

            if ($request->hasFile('image')) {
                $teamMember->image = TeamMember::storeImage($request->file('image'));
            }

            $teamMember->save();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.team_member.team_member_created'),
                'team_member' => new TeamMemberResource($teamMember),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.team_member.failed_to_create_team_member'), $e);
        }
    }

    public function update(TeamMemberRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $teamMember = TeamMember::findOrFail($id);

            $teamMember->fill([
                'name' => $request->input('name'),
                'occupation' => $request->input('occupation'),
                'is_active' => $request->boolean('is_active', $teamMember->is_active),
                'arrangement' => TeamMember::updateArrangement($teamMember, $request->input('arrangement', $teamMember->arrangement)),
            ]);

            if ($request->hasFile('image')) {
                TeamMember::deleteImage($teamMember->getRawOriginal('image'));
                $teamMember->image = TeamMember::storeImage($request->file('image'));
            }

            $teamMember->save();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.team_member.team_member_updated'),
                'team_member' => new TeamMemberResource($teamMember),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.team_member.failed_to_update_team_member'), $e);
        }
    }

    public function destroy(TeamMember $teamMember)
    {
        try {
            DB::beginTransaction();

            TeamMember::rearrangeAfterDelete($teamMember->arrangement);

            TeamMember::deleteImage($teamMember->getRawOriginal('image'));

            $teamMember->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.team_member.team_member_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.team_member.failed_to_delete_team_member'), $e);
        }
    }


}
