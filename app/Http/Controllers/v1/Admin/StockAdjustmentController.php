<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\StockAdjustmentRequest;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Http\Resources\V1\Admin\StockAdjustmentResource;
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
                $search = $validated['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('reason', 'like', "%$search%")
                        ->orWhereHas('variant', function ($q2) use ($search) {
                            $q2->where('sku', 'like', "%$search%");
                        });
                });
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

        if (!in_array($data['direction'], ['increase', 'decrease'])) {
            return response()->json([
                'result' => false,
                'message' => __('messages.stock_adjustment.invalid_direction'),
            ]);
        }

        $quantityChange = $data['direction'] === 'increase'
            ? abs($data['quantity'])
            : -abs($data['quantity']);

        $data['type'] = 'manual';
        $data['adjusted_by'] = Auth::id();
        $data['quantity'] = $quantityChange;
        $data['parent_adjustment_id'] = null; // Explicit for consistency

        try {
            DB::transaction(function () use (&$data, &$stock, &$adjustment) {
                if ($data['direction'] === 'decrease') {
                    $existingStock = Stock::where([
                        'variant_id' => $data['variant_id'],
                        'warehouse_id' => $data['warehouse_id'],
                        'shelf_id' => $data['shelf_id'] ?? null,
                    ])->first();

                    if (!$existingStock || $existingStock->quantity < abs($data['quantity'])) {
                        throw new Exception(__('messages.stock_adjustment.insufficient_stock'));
                    }
                }

                $stock = StockAdjustment::updateStockQuantity(
                    $data['variant_id'],
                    $data['warehouse_id'],
                    $data['shelf_id'] ?? null,
                    $data['quantity']
                );

                $adjustment = StockAdjustment::createAdjustment($data);
            });

            return response()->json([
                'result' => true,
                'message' => __('messages.stock_adjustment.adjusted'),
                'stock' => $stock,
                'adjustment' => $adjustment,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.stock_adjustment.failed_to_adjust'), $e);
        }
    }
}
