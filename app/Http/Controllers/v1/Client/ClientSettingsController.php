<?php

namespace App\Http\Controllers\V1\Client;

use App\Helpers\SortOptions;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\ConfigurationResource;
use App\Http\Resources\V1\Client\BrandResource;
use App\Http\Resources\V1\Client\CategoryResource;
use App\Http\Resources\V1\Client\ColorResource;
use App\Http\Resources\V1\Client\ColorSeasonResource;
use App\Http\Resources\V1\Client\FilterHomeSectionResource;
use App\Http\Resources\V1\Client\OccupationResource;
use App\Http\Resources\V1\Client\SizeResource;
use App\Http\Resources\V1\Client\SortResource;
use App\Http\Resources\V1\Client\TagResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\ColorSeason;
use App\Models\Configuration;
use App\Models\HomeSection;
use App\Models\Occupation;
use App\Models\Product;
use App\Models\Size;
use App\Models\Tag;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class  ClientSettingsController extends Controller
{

    public function index(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->withCount(['products as count' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();
        $brands = Brand::where('is_active', true)
            ->withCount(['products as count' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();


        $homeSections = HomeSection::where('type', 'product_section')
            ->withCount(['productSectionItems as count' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get(['id', 'title']);
        $priceRangeQuery = Product::select(DB::raw('MIN(price - (price * discount / 100)) as min_price'), DB::raw('MAX(price - (price * discount / 100)) as max_price'))->first();

        $minPrice = $priceRangeQuery->min_price;
        $maxPrice = $priceRangeQuery->max_price;

        try {
            return response()->json([
                'result' => true,
                'message' => __('messages.retrieved_successfully'),
                'categories' => CategoryResource::collection($categories),
                'brands' => BrandResource::collection($brands),
                'color_seasons' => ColorSeasonResource::collection(ColorSeason::all()),
                'colors' => ColorResource::collection(Color::with('colorSeason')->get()),
                'configurations' => ConfigurationResource::collection(Configuration::all()),
                'occupations' => OccupationResource::collection(Occupation::all()),
                'sizes' => SizeResource::collection(Size::all()),
                'tags' => TagResource::collection(Tag::all()),
                'sorts' => SortResource::collection(collect(SortOptions::list())),
                'homeSections' => FilterHomeSectionResource::collection(collect($homeSections)),
                'price_range' => [
                    'min' => max((float) $minPrice - 10, 0),
                    'max' => (float) $maxPrice + 10,
                ],
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
