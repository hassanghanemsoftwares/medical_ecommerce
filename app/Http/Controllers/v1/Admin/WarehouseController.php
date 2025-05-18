<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\WarehouseRequest;
use App\Http\Resources\V1\WarehouseResource;
use App\Http\Resources\V1\PaginationResource;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name,location',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $warehouses = Warehouse::when(
                $validated['search'] ?? null,
                fn($q, $search) =>
                $q->where('name', 'like', "%$search%")
                    ->orWhere('location', 'like', "%$search%")
            )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.warehouse.warehouses_retrieved'),
                'warehouses' => WarehouseResource::collection($warehouses),
                'pagination' => new PaginationResource($warehouses),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.warehouse.failed_to_retrieve_data', $e);
        }
    }

    public function show(Warehouse $warehouse)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.warehouse.warehouse_found'),
            'warehouse' => new WarehouseResource($warehouse),
        ]);
    }

    public function store(WarehouseRequest $request)
    {
        try {
            DB::beginTransaction();

            $warehouse = Warehouse::create($request->only(['name', 'location']));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.warehouse.warehouse_created'),
                'warehouse' => new WarehouseResource($warehouse),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.warehouse.failed_to_create_warehouse', $e);
        }
    }

    public function update(WarehouseRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $warehouse = Warehouse::findOrFail($id);
            $warehouse->update($request->only(['name', 'location']));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.warehouse.warehouse_updated'),
                'warehouse' => new WarehouseResource($warehouse),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.warehouse.failed_to_update_warehouse', $e);
        }
    }

    public function destroy(Warehouse $warehouse)
    {
        try {
            DB::beginTransaction();

            $warehouse->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.warehouse.warehouse_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.warehouse.failed_to_delete_warehouse', $e);
        }
    }
}
