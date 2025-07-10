<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ReturnOrderRequest;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Http\Resources\V1\Admin\ReturnOrderResource;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\ReturnOrderDetail;
use App\Models\StockAdjustment;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;



class ReturnOrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:requested_at,status',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $returns = ReturnOrder::with(['order', 'order.client'])
                ->when($validated['search'] ?? null, function ($query, $search) {
                    $query->whereHas('order', function ($q) use ($search) {
                        $q->where('order_number', 'like', "%$search%");
                    });
                })
                ->orderBy($validated['sort'] ?? 'requested_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.return_order.return_orders_retrieved'),
                'return_orders' => ReturnOrderResource::collection($returns),
                'pagination' => new PaginationResource($returns),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.return_order.failed_to_retrieve_data'), $e);
        }
    }

    public function show(ReturnOrder $returnOrder)
    {
        $returnOrder->load([
            'order.client',
            'order',
            'details.variant.product',
            'details.variant.size',
            'details.variant.color',
        ]);

        return response()->json([
            'result' => true,
            'message' => __('messages.return_order.return_order_found'),
            'return_order' => new ReturnOrderResource($returnOrder),
        ]);
    }

    public function store(ReturnOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $totalRefund = 0;
            $details = [];

            $order = Order::with('orderDetails')->find($data['order_id']);

            if (!$order) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.return_order.invalid_order'),
                ]);
            }

            $orderItems = $order->orderDetails->keyBy('variant_id');

            foreach ($data['products'] as $product) {
                $variantId = $product['variant_id'];
                $quantity = $product['quantity'];

                if (!isset($orderItems[$variantId])) {
                    return response()->json([
                        'result' => false,
                        'message' => __('messages.return_order.variant_not_in_order'),
                    ]);
                }

                $orderedQty = $orderItems[$variantId]->quantity;

                if ($quantity > $orderedQty) {
                    return response()->json([
                        'result' => false,
                        'message' => __('messages.return_order.quantity_exceeds_ordered'),
                    ]);
                }

                $variant = Variant::with('product')->find($variantId);

                if (!$variant) {
                    return response()->json([
                        'result' => false,
                        'message' => __('messages.return_order.invalid_variant'),
                    ]);
                }

                $price = $variant->product->price;
                $refund = $price * $quantity;
                $totalRefund += $refund;

                $details[] = [
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'refund_amount' => $refund,
                ];
            }
            $returnOrderData = [
                'return_order_number' => ReturnOrder::generateOrderNumber(),
                'created_by' => Auth::id(),
                'requested_at' => now(),
                'status' => 0,
                'order_id' => $data['order_id'],
                'client_id' => $order->client_id,
                'reason' => $data['reason'] ?? '',
            ];

            $returnOrder = ReturnOrder::create($returnOrderData);

            foreach ($details as $detail) {
                $detail['return_order_id'] = $returnOrder->id;
                ReturnOrderDetail::create($detail);
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.return_order.return_order_created'),
                'return_order' => new ReturnOrderResource($returnOrder->load('details')),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.return_order.failed_to_create_return_order'), $e);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|integer|min:0|max:3',
        ]);

        try {
            DB::beginTransaction();

            $returnOrder = ReturnOrder::with(['details.variant', 'order.orderDetails'])->findOrFail($id);

            $currentStatusKey = ReturnOrder::getStatusKey($returnOrder->status['name'] ?? '');

            if (!in_array($validated['status'], ReturnOrder::getStatusTransitions()[$currentStatusKey] ?? [])) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.return_order.invalid_status_transition'),
                ]);
            }

            if ((int) $validated['status'] === 1 && (int) ReturnOrder::getStatusKey($returnOrder->status['name'] ?? '') !== 1) {
                $order = $returnOrder->order;

                if ($order) {
                    $order->update(['status' => 9]);

                    foreach ($returnOrder->details as $detail) {
                        $variant = $detail->variant;

                        $firstAdjustment = $variant->stockAdjustments()->where('reference_id', $order->id)
                            ->where('reference_type', 'order')
                            ->first();

                        if ($firstAdjustment) {
                            StockAdjustment::systemAdjust([
                                'variant_id' => $variant->id,
                                'warehouse_id' => $firstAdjustment->warehouse_id,
                                'shelf_id' => $firstAdjustment->shelf_id,
                                'type' => 'return',
                                'quantity' => $detail->quantity,
                                'cost_per_item' => $firstAdjustment->cost_per_item,
                                'reason' => __('messages.stock_adjustment.returned_due_to_return_order', ['number' => $order->order_number]),
                                'reference_id' => $returnOrder->id,
                                'reference_type' => 'return_order',
                            ]);
                        }
                    }
                }
            }

            $returnOrder->update(['status' => $validated['status']]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.return_order.updated_successfully'),
                'return_order' => new ReturnOrderResource($returnOrder->fresh('details')),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.return_order.failed_to_update'), $e);
        }
    }
}
