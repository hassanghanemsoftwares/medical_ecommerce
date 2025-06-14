<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\OccupationRequest;
use App\Http\Resources\V1\Admin\OccupationResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\Occupation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class OccupationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $occupations = Occupation::query()
                ->when($validated['search'] ?? null, fn($q, $search) =>
                    $q->where('name->en', 'like', "%$search%")
                      ->orWhere('name->ar', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.occupation.occupations_retrieved'),
                'occupations' => OccupationResource::collection($occupations),
                'pagination' => new PaginationResource($occupations),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse( __('messages.learning_video.failed_to_retrieve_data'), $e);
        }
    }

    public function show(Occupation $occupation)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.occupation.occupation_found'),
            'occupation' => new OccupationResource($occupation),
        ]);
    }

    public function store(OccupationRequest $request)
    {
        try {
            DB::beginTransaction();

            $occupation = Occupation::create([
                'name' => $request->input('name'),
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.occupation.occupation_created'),
                'occupation' => new OccupationResource($occupation),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __('messages.learning_video.failed_to_create_occupation'), $e);
        }
    }

    public function update(OccupationRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $occupation = Occupation::findOrFail($id);

            $occupation->update([
                'name' => $request->input('name'),
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.occupation.occupation_updated'),
                'occupation' => new OccupationResource($occupation),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __('messages.learning_video.failed_to_update_occupation'), $e);
        }
    }

    public function destroy(Occupation $occupation)
    {
        try {
            DB::beginTransaction();

            $occupation->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.occupation.occupation_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __('messages.learning_video.failed_to_delete_occupation'), $e);
        }
    }


}
