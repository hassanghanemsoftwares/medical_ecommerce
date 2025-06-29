<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\OrderResource;
use App\Http\Resources\V1\Client\PaginationResource;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;

class ClientOrdersController extends Controller
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
            $clientId = $request->user()->id;
            $orders = Order::with([         'client',
                'coupon',
                'address',
                'orderDetails.variant.product.category',
                'orderDetails.variant.product.brand',
                'orderDetails.variant.product.images',
                'orderDetails.variant.size',
                'orderDetails.variant.color',
                'orderDetails.variant.product.tags',
                'orderDetails.variant.product.specifications',])->where('is_cart', false)->where('is_preorder', false)
                ->where('client_id', $clientId)->when($validated['search'] ?? null, function ($query, $search) {
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
            return $this->errorResponse(__('messages.order.failed_to_retrieve_data'), $e);
        }
    }
}
