<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\HomeSectionRequest;
use App\Http\Resources\V1\Admin\HomeSectionResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\HomeSection;
use App\Models\HomeBanner;
use App\Models\HomeProductSectionItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeSectionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:title,created_at,type,arrangement,is_active',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = HomeSection::with(['banners', 'productSectionItems.product']);

            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('type', 'like', "%$search%")
                        ->orWhere('title->en', 'like', "%$search%")
                        ->orWhere('title->ar', 'like', "%$search%");
                });
            }

            $homeSections = $query->orderBy(
                $validated['sort'] ?? 'created_at',
                $validated['order'] ?? 'desc'
            )->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.sections_retrieved'),
                'home_sections' => HomeSectionResource::collection($homeSections),
                'pagination' => new PaginationResource($homeSections),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.home_section.failed_to_retrieve_data'), $e);
        }
    }

    public function show(HomeSection $homeSection)
    {
        $homeSection->load(['banners', 'productSectionItems.product']);

        return response()->json([
            'result' => true,
            'message' => __('messages.home_section.section_found'),
            'home_section' => new HomeSectionResource($homeSection),
        ]);
    }

    public function store(HomeSectionRequest $request)
    {
        DB::beginTransaction();
        try {
            $homeSection = HomeSection::create([
                'type' => $request->input('type'),
                'title' => $request->input('title'),
                'arrangement' => HomeSection::max('arrangement') + 1,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($request->has('banners')) {
                foreach ($request->input('banners') as $index => $bannerData) {
                    $banner = new HomeBanner();
                    $banner->home_section_id = $homeSection->id;
                    $banner->link = $bannerData['link'] ?? null;
                    $banner->title = $bannerData['title'] ?? null;
                    $banner->subtitle = $bannerData['subtitle'] ?? null;
                    $banner->arrangement = $bannerData['arrangement'] ?? (HomeBanner::max('arrangement') + 1);
                    $banner->is_active = $bannerData['is_active'] ?? true;
                    if ($request->hasFile("banners.$index.image")) {
                        $imageFile = $request->file("banners.$index.image");
                        $banner->image = HomeBanner::storeImage($imageFile);
                    }
                    if ($request->hasFile("banners.$index.image480w")) {
                        $image480wFile = $request->file("banners.$index.image480w");
                        $banner->image480w = HomeBanner::storeImage($image480wFile);
                    }


                    $banner->save();
                }
            }

            if ($request->has('product_section_items')) {
                foreach ($request->input('product_section_items') as $itemData) {
                    HomeProductSectionItem::create([
                        'home_section_id' => $homeSection->id,
                        'product_id' => $itemData['product_id'],
                        'arrangement' => $itemData['arrangement'] ?? (HomeProductSectionItem::max('arrangement') + 1),
                        'is_active' =>  $itemData['is_active'] ?? true,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.section_created'),
                'home_section' => new HomeSectionResource($homeSection->load(['banners', 'productSectionItems.product'])),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.home_section.failed_to_create_section'), $e);
        }
    }

    public function update(HomeSectionRequest $request, HomeSection $homeSection)
    {
        DB::beginTransaction();
        try {
            $homeSection->update([
                'type' => $request->input('type', $homeSection->type),
                'title' => $request->input('title', $homeSection->title),
                'arrangement' => HomeSection::updateArrangement($homeSection, $request->input('arrangement', $homeSection->arrangement)),
                'is_active' => $request->boolean('is_active', $homeSection->is_active),
            ]);

            if ($request->has('banners')) {
                foreach ($request->input('banners') as $index => $bannerData) {
                    if (isset($bannerData['id'])) {
                        $banner = HomeBanner::find($bannerData['id']);
                        if ($banner && $banner->home_section_id == $homeSection->id) {
                            $banner->link = $bannerData['link'] ?? $banner->link;
                            $banner->title = $bannerData['title'] ?? $banner->title;
                            $banner->subtitle = $bannerData['subtitle'] ?? $banner->subtitle;
                            $banner->arrangement = $bannerData['arrangement'] ?? $banner->arrangement;

                            if ($request->hasFile("banners.$index.image")) {
                                HomeBanner::deleteImage($banner->getRawOriginal('image'));
                                $imageFile = $request->file("banners.$index.image");
                                $banner->image = HomeBanner::storeImage($imageFile);
                            }
                            if ($request->hasFile("banners.$index.image480w")) {
                                HomeBanner::deleteImage($banner->getRawOriginal('image480w'));
                                $image480wFile = $request->file("banners.$index.image480w");
                                $banner->image480w = HomeBanner::storeImage($image480wFile);
                            }
                            $banner->save();
                        }
                    } else {
                        $banner = new HomeBanner();
                        $banner->home_section_id = $homeSection->id;
                        $banner->link = $bannerData['link'] ?? null;
                        $banner->title = $bannerData['title'] ?? null;
                        $banner->subtitle = $bannerData['subtitle'] ?? null;
                        $banner->arrangement = $bannerData['arrangement'] ?? (HomeBanner::max('arrangement') + 1);

                        if ($request->hasFile("banners.$index.image")) {
                            $imageFile = $request->file("banners.$index.image");
                            $banner->image = HomeBanner::storeImage($imageFile);
                        }
                        if ($request->hasFile("banners.$index.image480w")) {
                            $image480wFile = $request->file("banners.$index.image480w");
                            $banner->image480w = HomeBanner::storeImage($image480wFile);
                        }
                        $banner->save();
                    }
                }
            }

            if ($request->has('product_section_items')) {
                HomeProductSectionItem::where('home_section_id', $homeSection->id)->delete();
                foreach ($request->input('product_section_items') as $itemData) {
                    if (isset($itemData['id'])) {
                        $item = HomeProductSectionItem::find($itemData['id']);
                        if ($item && $item->home_section_id == $homeSection->id) {
                            $item->product_id = $itemData['product_id'] ?? $item->product_id;
                            $item->arrangement = $itemData['arrangement'] ?? $item->arrangement;
                            $item->save();
                        }
                    } else {
                        HomeProductSectionItem::create([
                            'home_section_id' => $homeSection->id,
                            'product_id' => $itemData['product_id'],
                            'arrangement' => $itemData['arrangement'] ?? (HomeProductSectionItem::max('arrangement') + 1),
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.section_updated'),
                'home_section' => new HomeSectionResource($homeSection->load(['banners', 'productSectionItems.product'])),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.home_section.failed_to_update_section'), $e);
        }
    }

    public function destroy(HomeSection $homeSection)
    {
        DB::beginTransaction();
        try {
            foreach ($homeSection->banners as $banner) {
                HomeBanner::deleteImage($banner->getRawOriginal('image'));
                HomeBanner::deleteImage($banner->getRawOriginal('image480w'));
            }

            HomeSection::rearrangeAfterDelete($homeSection->arrangement);

            $homeSection->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.section_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.home_section.failed_to_delete_section'), $e);
        }
    }
}
