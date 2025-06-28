<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\HomeSectionResource;
use App\Models\HomeSection;
use Exception;
use Illuminate\Support\Facades\DB;

class ClientHomeController extends Controller
{
    public function index()
    {
        try {
            $homeSections = HomeSection::with([
                'banners',
                'productSectionItems' => function ($query) {
                    $query->whereHas('product', function ($q) {
                        $q->where('availability_status', '!=', 'discontinued');
                    });
                },
                'productSectionItems.product' => function ($query) {
                    $query->where('availability_status', '!=', 'discontinued');
                },
                'productSectionItems.product.category',
                'productSectionItems.product.brand',
                'productSectionItems.product.variants',
                'productSectionItems.product.variants.color',
                'productSectionItems.product.variants.size',

            ])->orderBy("arrangement", "asc")->get();
            foreach ($homeSections as $section) {
                foreach ($section->productSectionItems as $item) {
                    if ($item->product) {
                        $item->product->updateAvailabilityStatus();
                    }
                }
            }
            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.sections_retrieved'),
                'home_sections' => HomeSectionResource::collection($homeSections),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
