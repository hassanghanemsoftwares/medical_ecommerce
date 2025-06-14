<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\OrderRequest;
use App\Http\Resources\V1\Admin\OrderResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\Address;
use App\Models\Configuration;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\StockAdjustment;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,order_number,status,delivery_amount',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $orders = Order::with(['client', 'coupon', 'address'])->where('is_cart', false)->where('is_preorder', false)
                ->when($validated['search'] ?? null, function ($query, $search) {
                    $query->where('order_number', 'like', "%$search%");
                })
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.order.orders_retrieved'),
                'orders' => OrderResource::collection($orders),
                'pagination' => new PaginationResource($orders),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse( __('messages.order.failed_to_retrieve_data'), $e);
        }
    }

    public function show(Order $order)
    {
        $order->load([
            'client',
            'coupon',
            'address',
            'orderDetails.variant.product',
            'orderDetails.variant.size',
            'orderDetails.variant.color',
        ]);

        if (!$order->is_view) {
            $order->is_view = true;
            $order->update();
        }

        return response()->json([
            'result' => true,
            'message' => __('messages.order.order_found'),
            'order' => new OrderResource($order),
        ]);
    }

    public function store(OrderRequest $request)
    {
        $data = $request->validated();
        $total = 0;
        $validatedProducts = [];

        try {
            DB::beginTransaction();

            foreach ($data['products'] as $item) {
                $variant = Variant::with(['product', 'stocks'])->find($item['variant_id']);

                if (!$variant || !$variant->product) {
                    throw new Exception(__('messages.order.invalid_variant', [
                        'sku' => $item['variant_id'],
                    ]));
                }

                $price = $variant->product->price;
                $discount = $variant->product->discount;
                $discountedPrice = $price - ($price * $discount / 100);
                $total += $discountedPrice * $item['quantity'];

                $validatedProducts[] = [
                    'variant' => $variant,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'discount' => $discount,
                ];
            }

            // Handle coupon logic
            if (!empty($data['coupon_code'])) {
                $coupon = Coupon::where('code', $data['coupon_code'])->first();
                [$canUse, $reason] = $coupon?->canBeUsed() ?? [false, __('messages.order.invalid_coupon')];

                if (!$canUse) throw new Exception($reason);

                if ($coupon->min_order_amount && $total < $coupon->min_order_amount) {
                    throw new Exception(__('messages.order.min_amount_not_met', [
                        'amount' => $coupon->min_order_amount,
                    ]));
                }

                $data['coupon_id'] = $coupon->id;
                $data['coupon_value'] = $coupon->value;
                $data['coupon_type'] = $coupon->type;
                $coupon->increment('usage_count');
                if ($coupon->coupon_type === 4) {
                    $data['delivery_amount'] = 0;
                }
            }
            // Handle address
            $address = Address::find($data['address_id']);
            $data['address_info'] = $address?->toArray();
            $data['order_number'] = Order::generateOrderNumber();
            if (!isset($data['delivery_amount'])) {
                $data['delivery_amount'] = (float) Configuration::where('key', 'delivery_charge')->value('value');
            }
            $data['is_cart'] = false;
            $data['created_by'] = Auth::id();

            $order = Order::create(Arr::except($data, ['coupon_code', 'products']));

            foreach ($validatedProducts as $item) {
                StockAdjustment::deductForOrder($item['variant']->id, $item['quantity'], [
                    'reason' => 'Order #' . $order->order_number,
                    'reference_id' => $order->id,
                    'reference_type' => 'order',
                ]);

                $cost = $item['variant']->stockAdjustments()->latest()->value('cost_per_item') ?? 0;

                OrderDetail::create([
                    'order_id' => $order->id,
                    'variant_id' => $item['variant']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'discount' => $item['discount'],
                    'cost' => $cost,
                ]);
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.order.order_created'),
                'order' => new OrderResource($order),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __(__('messages.order.failed_to_create_order')), $e);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'nullable|integer|min:0|max:10',
            'payment_method' => 'nullable|integer|in:0',
            'payment_status' => 'nullable|integer|between:0,3',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::with('orderDetails.variant')->findOrFail($id);
            $oldStatus = $order->status;

            if (isset($validated['status'])) {
                $newStatus = $validated['status'];
                if ($newStatus !== $oldStatus && !$order->canTransitionTo($newStatus)) {
                    throw new Exception(__('messages.order.invalid_status_transition'));
                }
            }

            $order->update(array_filter($validated, fn($v) => !is_null($v)));

            // If status changed to canceled
            if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
                $newStatus = $validated['status'];
                $canceledStatuses = [7]; // Define as constant if needed

                if (in_array($newStatus, $canceledStatuses) && !in_array($oldStatus, $canceledStatuses)) {
                    foreach ($order->orderDetails as $detail) {
                        $adjustments = StockAdjustment::where([
                            ['variant_id', '=', $detail->variant_id],
                            ['reference_id', '=', $order->id],
                            ['reference_type', '=', 'order'],
                            ['type', '=', 'sale'],
                        ])->latest()->get();

                        foreach ($adjustments as $adj) {
                            StockAdjustment::systemAdjust([
                                'variant_id'     => $adj->variant_id,
                                'warehouse_id'   => $adj->warehouse_id,
                                'shelf_id'       => $adj->shelf_id,
                                'type'           => 'return',
                                'quantity'       => $adj->quantity,
                                'cost_per_item'  => $adj->cost_per_item,
                                'reason'         => 'Stock returned due to cancellation of Order #' . $order->order_number,
                                'reference_id'   => $order->id,
                                'reference_type' => 'order',
                            ]);
                        }
                    }
                }
            }
            if ($order->coupon_id) {
                $order->coupon->decrement('usage_count');
            }
            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.order.updated_successfully'),
                'order' => new OrderResource($order->load([
                    'client',
                    'coupon',
                    'address',
                    'orderDetails.variant.product',
                    'orderDetails.variant.size',
                    'orderDetails.variant.color',
                ])),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __(__('messages.order.failed_to_update_order')), $e);
        }
    }
}
