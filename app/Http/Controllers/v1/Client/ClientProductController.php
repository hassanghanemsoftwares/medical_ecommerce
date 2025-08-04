<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\ProductResource;
use App\Models\ClientSession;
use App\Models\Product;
use Exception;
use App\Models\ProductClick;
use Illuminate\Http\Request;

class ClientProductController extends Controller
{
    public function show(Request $request, $slug)
    {
        try {
            $product = Product::where("slug", $slug)->firstOrFail();
            $tracking = json_decode($request->header('X-Tracking-Data'), true) ?? [];
            $deviceId = $tracking['device_id'] ?? null;

            if ($deviceId) {
                // Get ClientSession by device_id (should exist because middleware runs before)
                $clientSession = ClientSession::where('device_id', $deviceId)->first();

                if ($clientSession) {
                    ProductClick::create([
                        'client_session_id' => $clientSession->id,
                        'product_id' => $product->id,
                    ]);
                }
            }
            $product->load([
                'category',
                'brand',
                'images' => fn($q) => $q->orderBy('arrangement', 'asc'),
                'variants.size',
                'variants.color',
                'tags',
                'specifications',
                'reviews' => fn($q) => $q
                    ->where('is_active', 1)
                    ->orderBy('id', 'desc')
                    ->take(5),

            ]);
            $relatedProductsQuery = Product::where('id', '!=', $product->id)
                ->where(function ($query) use ($product) {
                    $query->where('category_id', $product->category_id)
                        ->orWhere('brand_id', $product->brand_id)
                        ->orWhereHas('tags', function ($q) use ($product) {
                            $q->whereIn('tag_id', $product->tags->pluck('id'));
                        });
                })
                ->limit(5)
                ->with([
                    'category',
                    'brand',
                    'images' => fn($q) => $q->orderBy('arrangement', 'asc'),
                    'variants.size',
                    'variants.color',
                    'tags',
                    'specifications',
                    'reviews' => fn($q) => $q
                        ->where('is_active', 1)
                        ->orderBy('id', 'desc')
                        ->take(5),

                ]);

            $relatedProducts = $relatedProductsQuery->get();

            return response()->json([
                'result' => true,
                'message' => __('messages.product.product_found'),
                'product' => new ProductResource($product),
                'related_products' => ProductResource::collection($relatedProducts),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
