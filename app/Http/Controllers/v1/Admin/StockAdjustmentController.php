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
use InvalidArgumentException;

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
        $data = $request->validated();
        $data['type'] = "manual";
        if (!in_array($data['direction'], ['increase', 'decrease'])) {
            return response()->json([
                'result' => false,
                'message' => __('messages.stock_adjustment.invalid_direction'),
            ]);
        }

        $quantityChange = $data['direction'] === 'increase'
            ? $data['quantity']
            : -$data['quantity'];

        try {
            DB::beginTransaction();

            if ($data['direction'] === 'decrease') {
                $existingStock = Stock::where([
                    'variant_id' => $data['variant_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'shelf_id' => $data['shelf_id'] ?? null,
                ])->first();

                if (!$existingStock || $existingStock->quantity < abs($quantityChange)) {
                    return response()->json([
                        'result' => false,
                        'message' => __('messages.stock_adjustment.insufficient_stock'),
                    ]);
                }
            }

            $stock = StockAdjustment::updateStockQuantity(
                $data['variant_id'],
                $data['warehouse_id'],
                $data['shelf_id'] ?? null,
                $quantityChange
            );

            $adjustment = StockAdjustment::createAdjustment(array_merge($data, [
                'quantity' => $quantityChange,
                'adjusted_by' => Auth::id(),
            ]));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.stock_adjustment.adjusted'),
                'stock' => $stock,
                'adjustment' => $adjustment,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.stock_adjustment.failed_to_retrieve_data'), $e);
        }
    }

    public function destroy(StockAdjustment $adjustment)
    {
        try {
            DB::beginTransaction();

            $stock = Stock::where([
                'variant_id' => $adjustment->variant_id,
                'warehouse_id' => $adjustment->warehouse_id,
                'shelf_id' => $adjustment->shelf_id,
            ])->first();

            if (!$stock) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.stock_adjustment.not_found'),
                ]);
            }

            $revertedQty = $stock->quantity - $adjustment->quantity;

            if ($revertedQty < 0) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.stock_adjustment.negative_stock_error'),
                ]);
            }

            $stock->quantity = $revertedQty;
            $stock->save();
            $adjustment->delete();

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
}
