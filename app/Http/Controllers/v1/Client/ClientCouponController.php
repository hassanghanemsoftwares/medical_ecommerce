<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\CartResource;
use App\Models\Configuration;
use App\Models\Coupon;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientCouponController extends Controller
{
    protected function getCart($request)
    {
        $clientId = $request->user()->id;

        $cart = Order::where('client_id', $clientId)->where('is_cart', true)->first();

        return $cart;
    }


    public function apply(Request $request)
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string|exists:coupons,code',
        ]);

        try {
            $cart = $this->getCart($request);
            if (!$cart) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.cart.not_found'),
                ]);
            }

            $coupon = Coupon::where('code', $validated['coupon_code'])->first();

            [$canUse, $reason] = $coupon->canBeUsed() ?? [false, __('messages.order.invalid_coupon')];
            if (!$canUse) {
                return response()->json([
                    'result' => false,
                    'message' => $reason,
                ]);
            }

            if ($coupon->min_order_amount && $cart->subtotal < $coupon->min_order_amount) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.order.min_amount_not_met', [
                        'amount' => $coupon->min_order_amount,
                    ]),
                ]);
            }

            $cart->update([
                'coupon_id' => $coupon->id,
                'coupon_value' => $coupon->value,
                'coupon_type' => $coupon->type,
                'delivery_amount' => $coupon->coupon_type === 4 ? 0 : $cart->delivery_amount,
            ]);

            $coupon->increment('usage_count');

            return response()->json([
                'result' => true,
                'message' => __('messages.cart.coupon_applied'),
                'cart' => new CartResource($cart),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }


    public function remove(Request $request)
    {
        try {
            $cart = $this->getCart($request);

            if (!$cart) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.cart.not_found'),
                ]);
            }

            if (!$cart->coupon_id) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.cart.no_coupon_applied'),
                ]);
            }

            $coupon = Coupon::find($cart->coupon_id);
            if ($coupon && $coupon->usage_count > 0) {
                $coupon->decrement('usage_count');
            }

            $cart->update([
                'coupon_id' => null,
                'coupon_value' => null,
                'coupon_type' => null,
                'delivery_amount' => Configuration::getValue("delivery_charge"),
            ]);

            return response()->json([
                'result' => true,
                'message' => __('messages.cart.coupon_removed'),
                'cart' => new CartResource($cart),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
