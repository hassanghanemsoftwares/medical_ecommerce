<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\ProductResource;
use App\Models\Product;
use Exception;

class ClientProductController extends Controller
{
    public function show($slug)
    {
        try {
            $product = Product::where("slug", $slug)->firstOrFail();

            $product->load([
                'category',
                'brand',
                'images' => function ($query) {
                    $query->orderBy('arrangement', 'asc');
                },
                'variants' => function ($query) {
                    $query->where(function ($q) {
                        $q->whereNotNull('size_id')
                            ->orWhereNotNull('color_id');
                    })->with(['size', 'color']);
                },
                'tags',
                'specifications',
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
                    'images' => function ($query) {
                        $query->orderBy('arrangement', 'asc');
                    },
                    'variants' => function ($query) {
                        $query->where(function ($q) {
                            $q->whereNotNull('size_id')
                                ->orWhereNotNull('color_id');
                        })->with(['size', 'color']);
                    },
                    'tags',
                    'specifications',
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
