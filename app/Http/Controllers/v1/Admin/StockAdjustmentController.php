<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StockAdjustmentRequest;
use App\Http\Resources\V1\PaginationResource;
use App\Http\Resources\V1\StockAdjustmentResource;
use App\Models\Stock;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search'   => 'nullable|string|max:255',
                'sort'     => 'nullable|in:created_at,quantity,cost_per_item',
                'order'    => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = StockAdjustment::with(['warehouse', 'shelf', 'adjustedBy', 'variant']);

            if (!empty($validated['search'])) {
                $query->where('reason', 'like', '%' . $validated['search'] . '%');
            }

            $sort = $validated['sort'] ?? 'created_at';
            $order = $validated['order'] ?? 'desc';

            $adjustments = $query->orderBy($sort, $order)
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result'      => true,
                'message'     => __('messages.stock_adjustment.retrieved'),
                'adjustments' => StockAdjustmentResource::collection($adjustments),
                'pagination'  => new PaginationResource($adjustments),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.stock_adjustment.failed_to_retrieve_data'), $e);
        }
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.stock_adjustment.found'),
            'adjustment' => new StockAdjustmentResource($stockAdjustment->load(['warehouse', 'shelf', 'adjustedBy'])),
        ]);
    }

    public function manualAdjustWithDirection(StockAdjustmentRequest $request)
    {
        $request->validate([
            'direction' => ['required', 'in:increase,decrease'],
        ]);

        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Determine signed quantity change
            $quantityChange = ($data['direction'] === 'increase')
                ? $data['quantity']
                : -$data['quantity'];

            $stock = Stock::firstOrNew([
                'variant_id' => $data['variant_id'],
                'warehouse_id' => $data['warehouse_id'],
                'shelf_id' => $data['shelf_id'] ?? null,
            ]);

            // Defensive: ensure quantity is numeric and defaults to 0
            $currentQty = $stock->quantity ?? 0;
            $newQuantity = $currentQty + $quantityChange;

            if ($newQuantity < 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Insufficient stock to perform this operation.'
                ], 422);
            }

            $stock->quantity = $newQuantity;
            $stock->save();

            $adjustment = new StockAdjustment();
            $adjustment->variant_id    = $data['variant_id'];
            $adjustment->warehouse_id  = $data['warehouse_id'];
            $adjustment->shelf_id      = $data['shelf_id'] ?? null;
            $adjustment->type          = $data['type'];
            $adjustment->quantity      = $quantityChange; // signed quantity
            $adjustment->cost_per_item = $data['cost_per_item'] ?? null;
            $adjustment->reason        = $data['reason'] ?? null;
            $adjustment->adjusted_by   = Auth::id();
            $adjustment->save();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => 'Stock adjusted successfully',
                'stock' => $stock,
                'adjustment' => $adjustment,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.stock.failed_to_adjust', $e);
        }
    }

public function destroy(StockAdjustment $stockAdjustment)
{
    try {
        DB::beginTransaction();

        // Load related stock record
        $stock = Stock::where('variant_id', $stockAdjustment->variant_id)
            ->where('warehouse_id', $stockAdjustment->warehouse_id)
            ->where('shelf_id', $stockAdjustment->shelf_id)
            ->first();

        if (!$stock) {
            DB::rollBack();
            return response()->json([
                'result' => false,
                'message' => 'Related stock record not found.'
            ], 404);
        }

        // Calculate reverted quantity (undo the adjustment)
        $revertedQuantity = $stock->quantity - $stockAdjustment->quantity;

        // Make sure reverted quantity is not negative
        if ($revertedQuantity < 0) {
            DB::rollBack();
            return response()->json([
                'result' => false,
                'message' => 'Cannot delete adjustment because it would cause negative stock.'
            ], 422);
        }

        // Update stock quantity
        $stock->quantity = $revertedQuantity;
        $stock->save();

        // Delete the adjustment record
        $stockAdjustment->delete();

        DB::commit();

        return response()->json([
            'result' => true,
            'message' => __('messages.stock_adjustment.deleted'),
        ]);
    } catch (Exception $e) {
        DB::rollBack();
        return $this->errorResponse('messages.stock_adjustment.failed_to_delete_adjustment', $e);
    }
}


    /**
     * Internal method for system triggered stock adjustments
     * (e.g. purchase, sale, return, transfer, supplier_return)
     */
    public function systemAdjust(array $data): StockAdjustment
    {
        // $data must include:
        // variant_id, warehouse_id, shelf_id (nullable), type,
        // quantity (positive integer), cost_per_item (nullable),
        // reason (nullable), reference_id, reference_type

        $increaseTypes = ['purchase', 'return', 'transfer'];
        $decreaseTypes = ['sale', 'damage', 'supplier_return'];

        if (in_array($data['type'], $increaseTypes)) {
            $quantityChange = abs($data['quantity']);
        } elseif (in_array($data['type'], $decreaseTypes)) {
            $quantityChange = -abs($data['quantity']);
        } else {
            throw new \InvalidArgumentException('Invalid stock adjustment type for system adjustment.');
        }

        return DB::transaction(function () use ($data, $quantityChange) {

            $stock = Stock::firstOrNew([
                'variant_id' => $data['variant_id'],
                'warehouse_id' => $data['warehouse_id'],
                'shelf_id' => $data['shelf_id'] ?? null,
            ]);

            $currentQty = $stock->quantity ?? 0;
            $newQuantity = $currentQty + $quantityChange;

            if ($newQuantity < 0) {
                throw new \Exception('Insufficient stock for system adjustment.');
            }

            $stock->quantity = $newQuantity;
            $stock->save();

            $adjustment = new StockAdjustment();
            $adjustment->variant_id    = $data['variant_id'];
            $adjustment->warehouse_id  = $data['warehouse_id'];
            $adjustment->shelf_id      = $data['shelf_id'] ?? null;
            $adjustment->type          = $data['type'];
            $adjustment->quantity      = $quantityChange;
            $adjustment->cost_per_item = $data['cost_per_item'] ?? null;
            $adjustment->reason        = $data['reason'] ?? null;
            $adjustment->adjusted_by   = $data['adjusted_by'] ?? null; // system or user id
            $adjustment->reference_id  = $data['reference_id'] ?? null;
            $adjustment->reference_type = $data['reference_type'] ?? null;
            $adjustment->save();

            return $adjustment;
        });
    }
}
