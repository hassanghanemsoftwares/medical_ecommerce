<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\HomeSectionRequest;
use App\Http\Resources\V1\HomeSectionResource;
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
            return $this->errorResponse('failed_to_fetch_home_sections', $e);
        }
    }

    public function store(HomeSectionRequest $request)
    {
        try {
            $data = $request->validated();
            $homeSection = HomeSection::create($data);

            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.home_section_created'),
                'home_section' => new HomeSectionResource($homeSection),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('failed_to_create_home_section', $e);
        }
    }

    public function show($id)
    {
        try {
            $homeSection = HomeSection::findOrFail($id);
            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.home_section_fetched'),
                'home_section' => new HomeSectionResource($homeSection),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('failed_to_fetch_home_section', $e);
        }
    }

    public function update(HomeSectionRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $homeSection = HomeSection::findOrFail($id);
            $homeSection->update($data);

            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.home_section_updated'),
                'home_section' => new HomeSectionResource($homeSection),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('failed_to_update_home_section', $e);
        }
    }

    public function destroy($id)
    {
        try {
            $homeSection = HomeSection::findOrFail($id);
            $homeSection->delete();

            return response()->json([
                'result' => true,
                'message' => __('messages.home_section.home_section_deleted'),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('failed_to_delete_home_section', $e);
        }
    }

    private function errorResponse($messageKey, Exception $e)
    {
        return response()->json([
            'result' => false,
            'message' => __('messages.home_section.' . $messageKey),
            'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
        ]);
    }
}
