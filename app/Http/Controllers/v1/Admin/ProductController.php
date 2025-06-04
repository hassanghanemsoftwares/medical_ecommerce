<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ProductRequest;
use App\Http\Resources\V1\Admin\ProductResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\ProductTag;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductController extends Controller
{


    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name,price,discount,is_active,barcode,availability_status,category_name',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Product::query()
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
                ])
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->select('products.*');

            if (!empty($validated['search'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('products.name', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('products.barcode', 'like', '%' . $validated['search'] . '%');
                });
            }

            if (($validated['sort'] ?? null) === 'category_name') {
                $query->orderBy('categories.name', $validated['order'] ?? 'desc');
            } else {
                $query->orderBy($validated['sort'] ?? 'products.created_at', $validated['order'] ?? 'desc');
            }

            $products = $query->paginate($validated['per_page'] ?? 10);

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
            ])),
        ]);
    }

    public function store(ProductRequest $request)
    {
        try {
            DB::beginTransaction();
            $product = new Product($request->except(['tags', 'images', 'variants', 'specifications']));
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
                    $variant = new Variant([
                        'product_id' => $product->id,
                        'size_id' => $variantData['size_id'] ?? null,
                        'color_id' => $variantData['color_id'] ?? null,
                    ]);

                    $variant->sku = Variant::generateSku($product, $variant->color_id, $variant->size_id);
                    $variant->save();
                }
            }
            if ($request->filled('specifications')) {
                foreach ($request->specifications as $spec) {
                    ProductSpecification::create([
                        'product_id' => $product->id,
                        'description' => $spec['description'],
                    ]);
                }
            }

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

            $product->fill($request->except(['tags', 'images', 'variants', 'specifications']));
            $product->slug = null;
            $product->save();

            ProductTag::where("product_id", $product->id)->delete();
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
                    if ($request->hasFile("images.$index.image")) {
                        $arrangement = $imageData['arrangement'] ?? ProductImage::getNextArrangement();
                        ProductImage::shiftArrangementsForNewImage($product->id, $arrangement);
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image' => ProductImage::storeImage($request->file("images.$index.image")),
                            'arrangement' => $arrangement,
                            'is_active' => $imageData['is_active'] ?? true,
                        ]);
                    }
                }
            }

            if ($request->filled('variants')) {
                foreach ($request->variants as $variantData) {
                    $variant = new Variant([
                        'product_id' => $product->id,
                        'size_id' => $variantData['size_id'] ?? null,
                        'color_id' => $variantData['color_id'] ?? null,
                    ]);

                    $variant->sku = Variant::generateSku($product, $variant->color_id, $variant->size_id);
                    $variant->save();
                }
            }
            ProductSpecification::where('product_id', $product->id)->delete();

            if ($request->filled('specifications')) {
                foreach ($request->specifications as $spec) {
                    ProductSpecification::create([
                        'product_id' => $product->id,
                        'description' => $spec['description'],
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'result' => true,
                'message' => __('messages.product.product_updated'),
                'product' => new ProductResource($product->load([
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
                ])),
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

    public function generateBarcode()
    {

        $barcode = Product::generateBarcode();

        return response()->json([
            'result' => true,
            'message' =>  __('messages.product.success_barcode_generate'),
            'barcode' => $barcode,
        ]);
    }
}
