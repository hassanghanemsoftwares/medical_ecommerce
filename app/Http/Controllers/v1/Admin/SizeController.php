<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SizeRequest;
use App\Http\Resources\V1\SizeResource;
use App\Http\Resources\V1\PaginationResource;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SizeController extends Controller
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

            $sizes = Size::query()
                ->when(
                    $validated['search'] ?? null,
                    fn($q, $search) =>
                    $q->where('name->en', 'like', "%$search%")
                        ->orWhere('name->ar', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.size.sizes_retrieved'),
                'sizes' => SizeResource::collection($sizes),
                'pagination' => new PaginationResource($sizes),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.size.failed_to_retrieve_data', $e);
        }
    }

    public function show(Size $size)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.size.size_found'),
            'size' => new SizeResource($size),
        ]);
    }

    public function store(SizeRequest $request)
    {
        try {
            DB::beginTransaction();

            $size = Size::create($request->only(['name']));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.size.size_created'),
                'size' => new SizeResource($size),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.size.failed_to_create_size', $e);
        }
    }

    public function update(SizeRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $size = Size::findOrFail($id);
            $size->update($request->only(['name']));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.size.size_updated'),
                'size' => new SizeResource($size),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.size.failed_to_update_size', $e);
        }
    }

    public function destroy(Size $size)
    {
        try {
            DB::beginTransaction();
            $size->delete();
            DB::commit();
            return response()->json([
                'result' => true,
                'message' => __('messages.size.size_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.size.failed_to_delete_size', $e);
        }
    }
}
