<?php
namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ColorSeasonRequest;
use App\Http\Resources\V1\Admin\ColorSeasonResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\ColorSeason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ColorSeasonController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $colorSeasons = ColorSeason::query()
                ->when($validated['search'] ?? null, fn($q, $search) =>
                    $q->where('name->en', 'like', "%$search%")
                      ->orWhere('name->ar', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.color_season.color_seasons_retrieved'),
                'color_seasons' => ColorSeasonResource::collection($colorSeasons),
                'pagination' => new PaginationResource($colorSeasons),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.color_season.failed_to_retrieve_data', $e);
        }
    }

    public function show(ColorSeason $colorSeason)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.color_season.color_season_found'),
            'color_season' => new ColorSeasonResource($colorSeason),
        ]);
    }

    public function store(ColorSeasonRequest $request)
    {
        try {
            DB::beginTransaction();

            $colorSeason = ColorSeason::create([
                'name' => $request->input('name'),
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.color_season.color_season_created'),
                'color_season' => new ColorSeasonResource($colorSeason),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.color_season.failed_to_create_color_season', $e);
        }
    }

    public function update(ColorSeasonRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $colorSeason = ColorSeason::findOrFail($id);

            $colorSeason->update([
                'name' => $request->input('name'),
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.color_season.color_season_updated'),
                'color_season' => new ColorSeasonResource($colorSeason),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.color_season.failed_to_update_color_season', $e);
        }
    }

    public function destroy(ColorSeason $colorSeason)
    {
        try {
            DB::beginTransaction();

            $colorSeason->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.color_season.color_season_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.color_season.failed_to_delete_color_season', $e);
        }
    }

  
}
