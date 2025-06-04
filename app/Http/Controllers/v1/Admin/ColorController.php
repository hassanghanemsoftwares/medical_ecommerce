<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ColorRequest;
use App\Http\Resources\V1\Admin\ColorResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name,code',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $colors = Color::with('colorSeason')
                ->when($validated['search'] ?? null, fn($q, $search) =>
                    $q->where('name->en', 'like', "%$search%")
                      ->orWhere('name->ar', 'like', "%$search%")
                      ->orWhere('code', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.color.colors_retrieved'),
                'colors' => ColorResource::collection($colors),
                'pagination' => new PaginationResource($colors),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.color.failed_to_retrieve_data', $e);
        }
    }

    public function show(Color $color)
    {
        $color->load('colorSeason');

        return response()->json([
            'result' => true,
            'message' => __('messages.color.color_found'),
            'color' => new ColorResource($color),
        ]);
    }

    public function store(ColorRequest $request)
    {
        try {
            DB::beginTransaction();

            $color = Color::create([
                'name' => $request->input('name'),
                'code' => $request->input('code'),
                'color_season_id' => $request->input('color_season_id'),
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.color.color_created'),
                'color' => new ColorResource($color),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.color.failed_to_create_color', $e);
        }
    }

    public function update(ColorRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $color = Color::findOrFail($id);

            $color->update([
                'name' => $request->input('name'),
                'code' => $request->input('code'),
                'color_season_id' => $request->input('color_season_id'),
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.color.color_updated'),
                'color' => new ColorResource($color),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.color.failed_to_update_color', $e);
        }
    }

    public function destroy(Color $color)
    {
        try {
            DB::beginTransaction();

            $color->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.color.color_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.color.failed_to_delete_color', $e);
        }
    }


}
