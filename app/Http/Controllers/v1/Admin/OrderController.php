<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\OrderRequest;
use App\Http\Resources\V1\OrderResource;
use App\Http\Resources\V1\PaginationResource;
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

            $orders = Order::with(['client', 'coupon', 'address'])->where('is_cart', false)
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
            return $this->errorResponse('messages.order.failed_to_retrieve_data', $e);
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


        return response()->json([
            'result' => true,
            'message' => __('messages.order.order_found'),
            'order' => new OrderResource($order),
        ]);
    }

    public function store(OrderRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $total = 0;
            $validatedProducts = [];

            foreach ($data['products'] as $item) {
                $variant = Variant::with('product', 'stockAdjustments')->find($item['variant_id']);

                if (!$variant || !$variant->product) {
                    return response()->json([
                        'result' => false,
                        'message' => __('messages.order.invalid_variant', ['sku' => $item['variant_id']])
                    ]);
                }

                $availableQty = $variant->stockAdjustments()->sum('quantity');

                if ($item['quantity'] > $availableQty) {
                    return response()->json([
                        'result' => false,
                        'message' => __('messages.order.insufficient_stock', [
                            'sku' => $variant->display_sku,
                            'available' => $availableQty,
                        ]),
                    ]);
                }

                $price = $variant->product->price;
                $discount = $variant->product->discount;
                $discountedPrice = $price - ($price * $discount / 100);
                $total += $discountedPrice * $item['quantity'];


                $validatedProducts[] = [
                    'variant' => $variant,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'availableQty' => $availableQty,
                ];
            }

            // Handle Coupon
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::where('code', $request->coupon_code)->first();

                [$canUse, $reason] = $coupon->canBeUsed();

                if (!$canUse) {
                    return response()->json([
                        'result' => false,
                        'message' =>  $reason,
                    ]);
                }

                if ($coupon->min_order_amount !== null && $total < $coupon->min_order_amount) {
                    return response()->json([
                        'result' => false,
                        'message' => __('messages.order.min_amount_not_met', [
                            'amount' => $coupon->min_order_amount,
                        ]),
                    ]);
                }

                $data['coupon_id'] = $coupon->id;
                $data['coupon_value'] = $coupon->value;
                $data['coupon_type'] = $coupon->type;
                $coupon->increment('usage_count');
            }

            // Handle Address
            $address = Address::find($data['address_id']);
            $data['address_info'] = $address ? $address->toArray() : null;

            // Set Order Data
            $data['order_number'] = Order::generateOrderNumber();
            $data['delivery_amount'] = (float) Configuration::where('key', 'delivery_charge')->value('value');
            $data['is_cart'] = false;
            $data['created_by'] = Auth::id();

            $data = Arr::except($data, ['coupon_code', 'products']);

            // Create Order
            $order = Order::create($data);

            foreach ($validatedProducts as $item) {
                $variant = $item['variant'];
                $firstAdjustment = $variant->stockAdjustments()->first();

                if (!$firstAdjustment) {
                    continue;
                }

                OrderDetail::create([
                    'order_id' => $order->id,
                    'variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'discount' => $variant->product->discount,
                    'cost' => $firstAdjustment->cost_per_item ?? 0,
                ]);

                StockAdjustment::systemAdjust([
                    'variant_id' => $variant->id,
                    'warehouse_id' => $firstAdjustment->warehouse_id,
                    'shelf_id' => $firstAdjustment->shelf_id,
                    'type' => 'sale',
                    'quantity' => $item['quantity'],
                    'cost_per_item' => $firstAdjustment->cost_per_item,
                    'reason' => 'Order #' . $order->order_number,
                    'reference_id' => $order->id,
                    'reference_type' => 'order',
                ]);
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.order.order_created'). " ".  $total ,
                'order' => new OrderResource($order),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.order.failed_to_create_order', $e);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'nullable|integer|min:0|max:10',
            'payment_method' => 'nullable|integer|between:0,0',
            'payment_status' => 'nullable|integer|between:0,3',
        ]);

        try {
            $order = Order::findOrFail($id);

            $order->update(array_filter($validated, fn($v) => !is_null($v)));

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
        } catch (\Throwable $e) {
            return response()->json([
                'result' => false,
                'message' => __('messages.order.failed_to_update_order'),
            ]
        );
        }
    }
}
