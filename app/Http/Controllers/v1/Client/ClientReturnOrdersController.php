<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\OrderResource;
use App\Http\Resources\V1\Client\PaginationResource;
use App\Http\Resources\V1\Client\ReturnOrderResource;
use App\Models\Order;
use App\Models\ReturnOrder;
use Exception;
use Illuminate\Http\Request;

class ClientReturnOrdersController extends Controller
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
            $clientId = $request->user()->id;
            $returns = ReturnOrder::with(['order', 'order.client'])
                ->where('client_id', $clientId)->when($validated['search'] ?? null, function ($query, $search) {
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

    public function show(ReturnOrder $returnOrder, Request $request)
    {
        if ($returnOrder->client_id !== $request->user()->id) {
            return response()->json([
                'result' => false,
                'message' => __('messages.unauthorized_access'),
            ]);
        }

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
}
