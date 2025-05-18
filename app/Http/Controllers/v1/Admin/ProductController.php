<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Http\Resources\V1\PaginationResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductTag;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name,price,discount',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $products = Product::query()
                ->with([
                    'category',
                    'brand',
                    'images',
                    'variants.size',
                    'variants.color',
                    'tags'
                ])
                ->when(
                    $validated['search'] ?? null,
                    fn($q, $search) =>
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('barcode', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.product.products_retrieved'),
                'products' => ProductResource::collection($products),
                'pagination' => new PaginationResource($products),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.product.failed_to_retrieve_data', $e);
        }
    }

    public function show(Product $product)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.product.product_found'),
            'product' => new ProductResource($product->load([
                'category',
                'brand',
                'images',
                'variants.size',
                'variants.color',
                'tags'
            ])),
        ]);
    }

    public function store(ProductRequest $request)
    {
        try {
            DB::beginTransaction();
            $product = new Product($request->except(['tags', 'images', 'variants']));
            $product->slug = null;
            $product->save();

            if ($request->filled('tags')) {
                foreach ($request->tags as $tagId) {
                    ProductTag::create([
                        'product_id' => $product->id,
                        'tag_id' => $tagId,
                    ]);
                }
            }
            if ($request->has('images')) {
                foreach ($request->input('images') as $index => $imageData) {
                    // Only handle if an actual file was uploaded
                    if ($request->hasFile("images.$index.image")) {
                        $file = $request->file("images.$index.image");

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image' => ProductImage::storeImage($file),
                            'arrangement' => $imageData['arrangement'] ?? ProductImage::getNextArrangement(),
                            'is_active' => $imageData['is_active'] ?? true,
                        ]);
                    }
                }
            }

            if ($request->filled('variants')) {
                foreach ($request->variants as $variantData) {
                    Variant::create([
                        'product_id' => $product->id,
                        'size_id' => $variantData['size_id'],
                        'color_id' => $variantData['color_id'],
                       
                    ]);
                }
            }
            $product->load([
                'category',
                'brand',
                'images',
                'tags',
                'variants.size',
                'variants.color'
            ]);
            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.product.product_created'),
                'product' => new ProductResource($product),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.product.failed_to_create_product', $e);
        }
    }

    public function update(ProductRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $product = Product::findOrFail($id);

            // Update core fields
            $product->fill($request->except(['tags', 'images', 'variants']));
            $product->slug = null; // force re-slugging
            $product->save();

            // Sync tags (delete old and insert new)
            ProductTag::where("product_id", $product->id)->delete();
            if ($request->filled('tags')) {
                foreach ($request->tags as $tagId) {
                    ProductTag::create([
                        'product_id' => $product->id,
                        'tag_id' => $tagId,
                    ]);
                }
            }

            // Optional: Replace images if new ones are uploaded
            if ($request->has('images')) {
                foreach ($request->input('images') as $index => $imageData) {
                    // Only handle if an actual file was uploaded
                    if ($request->hasFile("images.$index.image")) {
                        $file = $request->file("images.$index.image");

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image' => ProductImage::storeImage($file),
                            'arrangement' => $imageData['arrangement'] ?? ProductImage::getNextArrangement(),
                            'is_active' => $imageData['is_active'] ?? true,
                        ]);
                    }
                }
            }


            // Replace variants
            // $product->variants()->delete();
            if ($request->filled('variants')) {
                foreach ($request->variants as $variantData) {
                    Variant::create([
                        'product_id' => $product->id,
                        'size_id' => $variantData['size_id'],
                        'color_id' => $variantData['color_id'],
                        
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'result' => true,
                'message' => __('messages.product.product_updated'),
                'product' => new ProductResource($product),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.product.failed_to_update_product', $e);
        }
    }

    public function destroy(Product $product)
    {
        try {
            DB::beginTransaction();
            foreach ($product->images as $image) {
                ProductImage::deleteImage($image->getRawOriginal('image'));
                $image->delete();
            }
            $product->delete();
            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.product.product_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.product.failed_to_delete_product', $e);
        }
    }
}
