<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\PreOrderRequest;
use App\Http\Resources\V1\Admin\OrderResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\Address;
use App\Models\Configuration;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Exception;

class PreOrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,order_number,status',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $orders = Order::with(['client', 'coupon', 'address'])
                ->where('is_cart', false)
                ->where('is_preorder', true)
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

    public function store(PreOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $total = 0;

            $validatedProducts = Order::validateAndPrepareProducts($data['products'], $total);
            $coupon = null;

            if (!empty($data['coupon_code'])) {
                $coupon = Order::applyCouponIfExists($data['coupon_code'], $total);
            }

            $address = Address::find($data['address_id']);
            $data['address_info'] = $address ? $address->toArray() : null;
            $data['order_number'] = Order::generateOrderNumber();
            $data['delivery_amount'] = (float) Configuration::where('key', 'delivery_charge')->value('value');
            $data['is_cart'] = false;
            $data['is_preorder'] = true;
            $data['created_by'] = Auth::id();

            if ($coupon) {
                $data['coupon_id'] = $coupon->id;
                $data['coupon_value'] = $coupon->value;
                $data['coupon_type'] = $coupon->type;
            }

            $data = Arr::except($data, ['coupon_code', 'products']);

            $order = Order::create($data);

            foreach ($validatedProducts as $item) {
                $variant = $item['variant'];
                $firstAdjustment = $variant->stockAdjustments()->first();
                OrderDetail::create([
                    'order_id' => $order->id,
                    'variant_id' => $item['variant']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'discount' => $item['variant']->product->discount,
                    'cost' => $firstAdjustment->cost_per_item ?? 0,

                ]);
            }

            if ($coupon) {
                $coupon->increment('usage_count');
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.order.order_created'),
                'order' => new OrderResource($order),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __('messages.order.failed_to_create_order'), $e);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'convert_to_order' => 'nullable|integer|between:0,1',
            'payment_method' => 'nullable|integer|in:0',
            'payment_status' => 'nullable|integer|between:0,3',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::with([
                'orderDetails.variant.stocks',
                'orderDetails.variant.product',
            ])->findOrFail($id);

            // Apply updates except convert_to_order
            $order->update(Arr::except(array_filter($validated, fn($v) => !is_null($v)), ['convert_to_order']));

            // Handle conversion from preorder to regular order
            if (!empty($validated['convert_to_order']) && $order->is_preorder) {
                foreach ($order->orderDetails as $detail) {
                    $variant = $detail->variant;

                    if (!$variant || !$variant->product) {
                        throw new Exception(__('messages.order.invalid_variant', [
                            'sku' => $detail->variant_id,
                        ]));
                    }

                    StockAdjustment::deductForOrder($variant->id, $detail->quantity, [
                        'reason' => 'Converted Order #' . $order->order_number,
                        'reference_id' => $order->id,
                        'reference_type' => 'order',
                    ]);
                }

                $order->update([
                    'is_preorder' => false,
                    'is_view' => false,
                ]);
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.order.updated_successfully'),
                'order' => new OrderResource($order->fresh([
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
