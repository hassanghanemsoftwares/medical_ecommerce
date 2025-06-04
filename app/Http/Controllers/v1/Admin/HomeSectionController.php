<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\HomeSectionRequest;
use App\Http\Resources\V1\Admin\HomeSectionResource;
use App\Models\HomeSection;
use Illuminate\Http\Request;
use Exception;

class HomeSectionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $homeSections = HomeSection::orderBy('arrangement')->get();
            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.home_sections_fetched'),
                'home_sections' => HomeSectionResource::collection($homeSections),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.home_section.failed_to_fetch_home_sections', $e);
        }
    }

    public function store(HomeSectionRequest $request)
    {
        try {
            $homeSection = HomeSection::create($request->validated());

            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.home_section_created'),
                'home_section' => new HomeSectionResource($homeSection),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.home_section.failed_to_create_home_section', $e);
        }
    }

    public function show(HomeSection $homeSection)
    {
        try {
            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.home_section_fetched'),
                'home_section' => new HomeSectionResource($homeSection),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.home_section.failed_to_fetch_home_section', $e);
        }
    }

    public function update(HomeSectionRequest $request, HomeSection $homeSection)
    {
        try {
            $homeSection->update($request->validated());

            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.home_section_updated'),
                'home_section' => new HomeSectionResource($homeSection),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.home_section.failed_to_update_home_section', $e);
        }
    }

    public function  destroy(HomeSection $homeSection)
    {
        try {
            $homeSection->delete();

            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.home_section_deleted'),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.home_section.failed_to_delete_home_section', $e);
        }
    }
}
