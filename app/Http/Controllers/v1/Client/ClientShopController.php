<?php

namespace App\Http\Controllers\V1\Client;

use App\Helpers\SortOptions;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\PaginationResource;
use App\Http\Resources\V1\Client\ProductResource;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientShopController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:' . implode(',', SortOptions::keys()),
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
                'categories' => 'nullable|array',
                'categories.*' => 'integer|distinct|min:1',
                'homeSections' => 'nullable|array',
                'homeSections.*' => 'integer|distinct|min:1',
                'sizes' => 'nullable|array',
                'sizes.*' => 'integer|distinct|min:1',
                'colors' => 'nullable|array',
                'colors.*' => 'integer|distinct|min:1',
                'brands' => 'nullable|array',
                'brands.*' => 'integer|distinct|min:1',
                'price_min' => 'nullable|numeric|min:0',
                'price_max' => 'nullable|numeric|min:0|gte:price_min',
                'page' => 'nullable|integer|min:1',
            ]);

            $baseQuery = Product::with([
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

                'homeProductSectionItems' => fn($q) => $q->where('is_active', true),
            ]);

            $baseQuery->where('availability_status', '!=', 'discontinued');

            if (!empty($validated['search'])) {
                $searchTerm = '%' . $validated['search'] . '%';
                $baseQuery->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                        ->orWhere('barcode', 'like', $searchTerm);
                });
            }

            if (!empty($validated['categories'])) {
                $baseQuery->whereIn('category_id', $validated['categories']);
            }

            if (!empty($validated['homeSections'])) {
                $homeSectionIds = $validated['homeSections'];
                $baseQuery->whereHas('homeProductSectionItems', function ($q) use ($homeSectionIds) {
                    $q->whereIn('home_section_id', $homeSectionIds)
                        ->where('is_active', true);
                });
            }

            if (!empty($validated['brands'])) {
                $baseQuery->whereIn('brand_id', $validated['brands']);
            }

            if (!empty($validated['sizes']) || !empty($validated['colors'])) {
                $sizeIds = $validated['sizes'] ?? [];
                $colorIds = $validated['colors'] ?? [];

                $baseQuery->whereHas('variants', function ($q) use ($sizeIds, $colorIds) {
                    if (!empty($sizeIds)) {
                        $q->whereIn('size_id', $sizeIds);
                    }
                    if (!empty($colorIds)) {
                        $q->whereIn('color_id', $colorIds);
                    }
                });
            }


            $query = clone $baseQuery;

            if (isset($validated['price_min'])) {
                $query->whereRaw('(price - (price * discount / 100)) >= ?', [$validated['price_min']]);
            }

            if (isset($validated['price_max'])) {
                $query->whereRaw('(price - (price * discount / 100)) <= ?', [$validated['price_max']]);
            }

            $sortKey = $validated['sort'] ?? 'newest';
            $sortOption = SortOptions::get($sortKey);

            if ($sortOption) {
                $column = $sortOption['column'];
                if ($column === 'products.price') {
                    $query->orderByRaw('(price - (price * discount / 100)) ' . $sortOption['direction']);
                } elseif ($column === 'products.name') {
                    $currentLocale = app()->getLocale();
                    $query->orderByRaw(
                        "LOWER(JSON_UNQUOTE(JSON_EXTRACT(products.name, '$.$currentLocale'))) " . $sortOption['direction']
                    );
                } else {
                    $query->orderBy($column, $sortOption['direction']);
                }
            }


            $perPage = $validated['per_page'] ?? 12;
            $products = $query->paginate($perPage);

            foreach ($products as $product) {
                $product->updateAvailabilityStatus();
            }

            return response()->json([
                'result' => true,
                'message' => __('messages.product.products_retrieved'),
                'products' => ProductResource::collection($products),
                'pagination' => new PaginationResource($products),

            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
