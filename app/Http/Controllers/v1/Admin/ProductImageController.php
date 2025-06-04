<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ProductImageRequest;
use App\Http\Resources\V1\Admin\ProductImageResource;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductImageController extends Controller
{
    public function update(ProductImageRequest $request, ProductImage $productImage)
    {
        try {
            DB::beginTransaction();

            $newArrangement = $request->input('arrangement', $productImage->arrangement);
            $newIsActive = $request->has('is_active') ? $request->boolean('is_active') : $productImage->is_active;

            // If the request sets this image to inactive, ensure at least one image remains active
            if (
                $productImage->is_active &&
                $newIsActive === false
            ) {
                $activeCount = ProductImage::where('product_id', $productImage->product_id)
                    ->where('id', '!=', $productImage->id)
                    ->where('is_active', true)
                    ->count();

                if ($activeCount === 0) {
                    return response()->json([
                        'result' => false,
                        'message' => __('messages.product.at_least_one_image_active'),
                    ], 422);
                }
            }

            $productImage->arrangement = ProductImage::updateArrangement($productImage, $newArrangement);
            $productImage->is_active = $newIsActive;
            $productImage->save();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.product.update_success'),
                'product_image' => new ProductImageResource($productImage),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update product image', ['error' => $e->getMessage()]);
            return response()->json([
                'result' => false,
                'message' => __('messages.product.update_error'),
            ], 500);
        }
    }


    public function destroy(ProductImage $productImage)
    {
        try {
            DB::beginTransaction();

            $productId = $productImage->product_id;

            // Count total images for this product
            $totalImages = ProductImage::where('product_id', $productId)->count();

            // Prevent deleting the last image
            if ($totalImages <= 1) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.product.at_least_one_image_required'),
                ]);
            }

            ProductImage::rearrangeAfterDelete($productImage->arrangement);
            ProductImage::deleteImage($productImage->getRawOriginal('image'));
            $productImage->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.product.update_success'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete product image', ['error' => $e->getMessage()]);
            return response()->json([
                'result' => false,
                'message' => __('messages.product.update_error'),
            ], 500);
        }
    }
}
