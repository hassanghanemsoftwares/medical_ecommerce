<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\CartItemRequest;
use App\Http\Resources\V1\Client\CartResource;
use App\Models\Configuration;
use App\Models\Order;
use App\Models\StockAdjustment;
use App\Models\Variant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientCartController extends Controller
{
    protected function getCart($request, $createIfNotExists = true, $is_preorder = false)
    {
        $clientId = $request->user()->id;

        $cart = Order::where('client_id', $clientId)->where('is_cart', true)->first();

        if (!$cart && $createIfNotExists) {
            $cart = Order::create([
                'client_id' => $clientId,
                'is_cart' => true,
                'is_preorder' => $is_preorder,
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


    public function index(Request $request)
    {
        try {
            $cart = $this->getCart($request, false);

            return response()->json([
                'result' => true,
                'message' => __('messages.cart.cart_retrieved'),
                'cart' => $cart ? new CartResource($cart) : null,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }

    public function addOrUpdate(CartItemRequest $request)
    {
        try {
            $variant = Variant::with('product')->findOrFail($request->variant_id);
            $is_preorder = false;

            if ($variant->product->availability_status == "discontinued") {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.cart.cannot_add_this_item_to_cart'),
                ]);
            }

            if ($variant->product->availability_status != "available") {
                $is_preorder = true;
            } else {
                StockAdjustment::checkVariantQty($request->variant_id, $request->quantity);
            }

            $cart = $this->getCart($request, true, $is_preorder);

            $existingOrderDetails = $cart->orderDetails;
            $hasPreorderItem = $existingOrderDetails->contains(function ($detail) {
                return $detail->variant->product->availability_status != 'available';
            });

            if (
                ($hasPreorderItem && !$is_preorder) ||  (!$hasPreorderItem && $is_preorder)
            ) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.cart.cannot_mix_preorder_and_regular'),
                ]);
            }

            $orderDetail = $existingOrderDetails->where('variant_id', $request->variant_id)->first();

            if ($orderDetail) {
                $oldqty = $orderDetail->quantity;
                $orderDetail->quantity = $request->quantity;
                $orderDetail->save();

                $cart->refresh();

                if ($cart->coupon && $cart->coupon->min_order_amount > $cart->subtotal) {
                    $orderDetail->quantity = $oldqty;
                    $orderDetail->save();

                    return response()->json([
                        'result' => false,
                        'message' => __('messages.cart.coupon_min_order_amount_failed'),
                    ]);
                }
            } else {
                $cart->orderDetails()->create([
                    'variant_id' => $request->variant_id,
                    'quantity' => $request->quantity,
                    'price' => $variant->product->price,
                    'discount' => $variant->product->discount ?? 0,
                    'cost' => 0,
                ]);
            }

            return response()->json([
                'result' => true,
                'message' => __('messages.cart.added'),
                'cart' => new CartResource($cart->fresh()),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }


    public function remove(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|integer|exists:variants,id',
        ]);

        try {
            $cart = $this->getCart($request, false);

            $orderDetail = $cart->orderDetails()->where('variant_id', $request->variant_id)->first();

            if (!$orderDetail) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.cart.item_not_found'),
                ], 404);
            }
            $newSubtotal = $cart->subtotal - $orderDetail->getTotalAttribute();
            $isLastItem = $cart->orderDetails()->count() === 1;
            if (!$isLastItem && $cart->coupon && $cart->coupon->min_order_amount > $newSubtotal) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.cart.coupon_min_order_amount_failed'),
                ]);
            }
            $orderDetail->delete();
            if ($isLastItem) {
                $cart->delete();
                return response()->json([
                    'result' => true,
                    'message' => __('messages.cart.removed_and_cart_deleted'),
                ]);
            }

            return response()->json([
                'result' => true,
                'message' => __('messages.cart.removed'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
