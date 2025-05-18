<?php
namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ShelfRequest;
use App\Http\Resources\V1\ShelfResource;
use App\Http\Resources\V1\PaginationResource;
use App\Models\Shelf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ShelfController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'warehouse_id' => 'nullable|integer|exists:warehouses,id',
                'sort' => 'nullable|in:created_at,name,location',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $shelves = Shelf::with('warehouse')  
            ->when($validated['search'] ?? null, fn($q, $search) =>
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('location', 'like', "%$search%")
                )
                ->when($validated['warehouse_id'] ?? null, fn($q, $warehouseId) =>
                    $q->where('warehouse_id', $warehouseId)
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.shelf.shelves_retrieved'),
                'shelves' => ShelfResource::collection($shelves),
                'pagination' => new PaginationResource($shelves),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.shelf.failed_to_retrieve_data', $e);
        }
    }

    public function show(Shelf $shelf)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.shelf.shelf_found'),
            'shelf' => new ShelfResource($shelf),
        ]);
    }

    public function store(ShelfRequest $request)
    {
        try {
            DB::beginTransaction();

            $shelf = Shelf::create($request->only(['warehouse_id', 'name', 'location']));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.shelf.shelf_created'),
                'shelf' => new ShelfResource($shelf),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.shelf.failed_to_create_shelf', $e);
        }
    }

    public function update(ShelfRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $shelf = Shelf::findOrFail($id);
            $shelf->update($request->only(['warehouse_id', 'name', 'location']));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.shelf.shelf_updated'),
                'shelf' => new ShelfResource($shelf),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.shelf.failed_to_update_shelf', $e);
        }
    }

    public function destroy(Shelf $shelf)
    {
        try {
            DB::beginTransaction();

            $shelf->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.shelf.shelf_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.shelf.failed_to_delete_shelf', $e);
        }
    }

 
}
