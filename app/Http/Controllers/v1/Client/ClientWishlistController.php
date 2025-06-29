<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\CartItemRequest;
use App\Http\Requests\V1\Client\WishlistItemRequest;
use App\Http\Resources\V1\Client\CartResource;
use App\Http\Resources\V1\Client\PaginationResource;
use App\Http\Resources\V1\Client\WishlistResource;
use App\Models\Configuration;
use App\Models\Order;
use App\Models\StockAdjustment;
use App\Models\Variant;
use App\Models\Wishlist;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientWishlistController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search'   => 'nullable|string|max:255',
                'sort'     => 'nullable|in:created_at',
                'order'    => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $clientId = $request->user()->id;

            $wishlistItems = Wishlist::with('product')
                ->where('client_id', $clientId)
                ->when($validated['search'] ?? null, function ($query, $search) {
                    $query->whereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%")
                            ->orWhere('sku', 'like', "%$search%");
                    });
                })
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result'     => true,
                'message'    => __('messages.wishlist.retrieved'),
                'wishlist'   => WishlistResource::collection($wishlistItems),
                'pagination' => new PaginationResource($wishlistItems),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }


    public function addOrRemove(WishlistItemRequest $request)
    {


        DB::beginTransaction();

        try {
            $clientId = $request->user()->id;
            $productId = $request->product_id;

            $wishlistItem = Wishlist::where('client_id', $clientId)
                ->where('product_id', $productId)
                ->first();

            if ($wishlistItem) {
                $wishlistItem->delete();

                DB::commit();

                return response()->json([
                    'result' => true,
                    'message' => __('messages.wishlist.removed'),
                ]);
            } else {
                Wishlist::create([
                    'client_id' => $clientId,
                    'product_id' => $productId,
                ]);

                DB::commit();

                return response()->json([
                    'result' => true,
                    'message' => __('messages.wishlist.added'),
                ]);
            }
        } catch (Exception $e) {
            DB::rollBack();

            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
