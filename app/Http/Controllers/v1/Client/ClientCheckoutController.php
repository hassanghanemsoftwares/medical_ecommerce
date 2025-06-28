<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\OrderResource;
use App\Models\Address;
use App\Models\Configuration;
use App\Models\Order;
use App\Models\StockAdjustment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientCheckoutController extends Controller
{
    protected function getCart($request, $createIfNotExists = true)
    {
        $clientId = $request->user()->id;

        $cart = Order::where('client_id', $clientId)->where('is_cart', true)->first();

        if (!$cart && $createIfNotExists) {
            $cart = Order::create([
                'client_id' => $clientId,
                'is_cart' => true,
                'order_number' => Order::generateOrderNumber(),
                'delivery_amount' => Configuration::getValue("delivery_charge"),
            ]);
        }

        if ($cart) {

            $cart->load([
                'client',
                'coupon',
                'address',
                'orderDetails.variant.product.category',
                'orderDetails.variant.product.brand',
                'orderDetails.variant.product.images',
                'orderDetails.variant.size',
                'orderDetails.variant.color',
                'orderDetails.variant.product.tags',
                'orderDetails.variant.product.specifications',
            ]);
        }

        return $cart;
    }


    public function placeOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'notes' => 'nullable|string|max:255',
                'payment_method' => 'required|integer|between:0,0',
                'address_id' => 'required|exists:addresses,id',
            ]);

            $cart = $this->getCart($request, false);

            if (!$cart || $cart->orderDetails->isEmpty()) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.cart.empty_cart'),
                ]);
            }

            $total = 0;
            foreach ($cart->orderDetails as $detail) {
                $product = $detail->variant->product;
                if (!$product) {
                    return response()->json([
                        'result' => false,
                        'message' => __('messages.order.invalid_variant', ['sku' => $detail->variant_id]),
                    ]);
                }
                $price = $product->price;
                $discount = $product->discount;
                $discountedPrice = $price - ($price * $discount / 100);
                $total += $discountedPrice * $detail->quantity;
            }

            if ($cart->coupon) {
                [$canUse, $reason] = $cart->coupon->canBeUsed() ?? [false, __('messages.order.invalid_coupon')];

                if (!$canUse) {
                    return response()->json([
                        'result' => false,
                        'message' => $reason,
                    ]);
                }

                if ($cart->coupon->min_order_amount && $total < $cart->coupon->min_order_amount) {
                    return response()->json([
                        'result' => false,
                        'message' => __('messages.order.min_amount_not_met', [
                            'amount' => $cart->coupon->min_order_amount,
                        ]),
                    ]);
                }
            }

            DB::beginTransaction();

            $cart->address_id = $validated['address_id'];
            $address = Address::find($validated['address_id']);
            $cart->address_info = $address?->toArray();
            $cart->notes = $validated['notes'] ?? null;
            $cart->is_cart = false;
            $cart->payment_method = $validated['payment_method'];
            $cart->payment_status = 0;

            if ($cart->coupon) {
                $cart->coupon_id = $cart->coupon->id;
                $cart->coupon_value = $cart->coupon->value;
                $cart->coupon_type = $cart->coupon->type;
                if ($cart->coupon->coupon_type === 4) {
                    $cart->delivery_amount = 0;
                }
            } else {
                $cart->delivery_amount = (float) Configuration::where('key', 'delivery_charge')->value('value');
            }

            $cart->save();

            foreach ($cart->orderDetails as $detail) {
                if (!$cart->is_preorder) {
                    StockAdjustment::deductForOrder($detail->variant_id, $detail->quantity, [
                        'reason' => 'Order #' . $cart->order_number,
                        'reference_id' => $cart->id,
                        'reference_type' => 'order',
                    ]);
                    $cost = $detail->variant->stockAdjustments()->latest()->value('cost_per_item') ?? 0;
                    $detail->cost = $cost;
                }

                $product = $detail->variant->product;
                $price = $product->price;
                $discount = $product->discount;

                $detail->price = $price;
                $detail->discount = $discount;
                $detail->save();
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.order.order_created'),
                'order' => new OrderResource($cart),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
