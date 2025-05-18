<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BrandResource;
use App\Http\Resources\V1\CategoryResource;
use App\Http\Resources\V1\ColorResource;
use App\Http\Resources\V1\ColorSeasonResource;
use App\Http\Resources\V1\ConfigurationResource;
use App\Http\Resources\V1\OccupationResource;
use App\Http\Resources\V1\ShelfResource;
use App\Http\Resources\V1\SizeResource;
use App\Http\Resources\V1\TagResource;
use App\Http\Resources\V1\WarehouseResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\ColorSeason;
use App\Models\Configuration;
use App\Models\Occupation;
use App\Models\Shelf;
use App\Models\Size;
use App\Models\Tag;
use App\Models\Warehouse;
use Exception;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{

    public function index(): JsonResponse
    {
        try {
            $permissions = Permission::select('id', 'name')
                ->whereNotIn('name', ['view-activity-logs'])
                ->orderBy('id', 'asc')
                ->get();

            $roles = Role::where('team_id', getPermissionsTeamId())
                ->select('id', 'name')
                ->get();

            return response()->json([
                'result' => true,
                'message' => __('messages.retrieved_successfully'),
                'permissions' => $permissions,
                'roles' => $roles,
                'categories' => CategoryResource::collection(Category::all()),
                'brands' => BrandResource::collection(Brand::all()),
                'color_seasons' => ColorSeasonResource::collection(ColorSeason::all()),
                'colors' => ColorResource::collection(Color::with('colorSeason')->get()),
                'configurations' => ConfigurationResource::collection(Configuration::all()),
                'occupations' => OccupationResource::collection(Occupation::all()),
                'shelves' => ShelfResource::collection(Shelf::with('warehouse')->get()),
                'sizes' => SizeResource::collection(Size::all()),
                'tags' => TagResource::collection(Tag::all()),
                'warehouses' => WarehouseResource::collection(Warehouse::all()),
            ]);
        } catch (Exception $e) {

            return $this->errorResponse('messages.failed_to_retrieve_data', $e);
        }
    }
}
