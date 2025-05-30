<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AddressResource;
use App\Http\Resources\V1\BrandResource;
use App\Http\Resources\V1\CategoryResource;
use App\Http\Resources\V1\ClientResource;
use App\Http\Resources\V1\ColorResource;
use App\Http\Resources\V1\ColorSeasonResource;
use App\Http\Resources\V1\ConfigurationResource;
use App\Http\Resources\V1\OccupationResource;
use App\Http\Resources\V1\ProductsVariantsResource;
use App\Http\Resources\V1\ShelfResource;
use App\Http\Resources\V1\SizeResource;
use App\Http\Resources\V1\TagResource;
use App\Http\Resources\V1\WarehouseResource;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\Color;
use App\Models\ColorSeason;
use App\Models\Configuration;
use App\Models\Occupation;
use App\Models\Shelf;
use App\Models\Size;
use App\Models\Tag;
use App\Models\Variant;
use App\Models\Warehouse;
use Exception;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function getAllClients(Request $request)
    {
        try {
            $perPage = $request->input('limit', 10);
            $page = $request->input('page', 1);
            $searchTerm = $request->input('search', '');

            $query = Client::with('occupation');

            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('phone', 'like', "%{$searchTerm}%");
                });
            }

            $clients = $query->orderBy('created_at', 'desc') // default sorting by created_at desc
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'result' => true,
                'message' => __('messages.client.clients_retrieved'),
                'clients' => ClientResource::collection($clients),
                'total' => $clients->total(),
                'page' => $clients->currentPage(),
                'limit' => $clients->perPage(),
                'last_page' => $clients->lastPage(),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.client.failed_to_retrieve_data', $e);
        }
    }
    public function getAllProductsVariants(Request $request)
    {
        try {
            $perPage = $request->input('limit', 20);
            $page = $request->input('page', 1);
            $searchTerm = $request->input('search', '');

            $query = Variant::with([
                'product',
                'product.category',
                'size',
                'color',
            ]);

            if ($searchTerm) {
                $locale = app()->getLocale();

                $query->where(function ($q) use ($searchTerm, $locale) {
                    $q->where('sku', 'like', '%' . $searchTerm . '%');
                    $q->orWhereHas('product', function ($productQuery) use ($searchTerm, $locale) {
                        $productQuery->where('barcode', 'like', '%' . $searchTerm . '%')
                            ->orWhere("name->{$locale}", 'like', '%' . $searchTerm . '%');
                    });
                    $q->orWhereHas('product.category', function ($categoryQuery) use ($searchTerm, $locale) {
                        $categoryQuery->where("name->{$locale}", 'like', '%' . $searchTerm . '%');
                    });
                });
            }

            $variants = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'result' => true,
                'message' => __('messages.product.products_retrieved'),
                'productVariants' => ProductsVariantsResource::collection($variants),
                'total' => $variants->total(),
                'page' => $variants->currentPage(),
                'limit' => $variants->perPage(),
                'last_page' => $variants->lastPage(),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.product.failed_to_retrieve_data', $e);
        }
    }

    public function getOrderableVariants(Request $request)
    {
        try {
            $perPage = $request->input('limit', 20);
            $page = $request->input('page', 1);
            $searchTerm = $request->input('search', '');

            $query = Variant::with([
                'product',
                'product.category',
                'size',
                'color',
            ])
                ->whereHas('product', function ($q) {
                    $q->where('availability_status', 'available');
                })
                ->whereHas('stocks', function ($q) {})
                ->withSum('stocks as total_stock_quantity', 'quantity')
                ->having('total_stock_quantity', '>', 0);

            if ($searchTerm) {
                $locale = app()->getLocale();

                $query->where(function ($q) use ($searchTerm, $locale) {
                    $q->where('sku', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('product', function ($productQuery) use ($searchTerm, $locale) {
                            $productQuery->where('barcode', 'like', '%' . $searchTerm . '%')
                                ->orWhere("name->{$locale}", 'like', '%' . $searchTerm . '%');
                        })
                        ->orWhereHas('product.category', function ($categoryQuery) use ($searchTerm, $locale) {
                            $categoryQuery->where("name->{$locale}", 'like', '%' . $searchTerm . '%');
                        });
                });
            }

            $variants = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'result' => true,
                'message' => __('messages.product.orderable_variants_retrieved'),
                'productVariants' => ProductsVariantsResource::collection($variants),
                'total' => $variants->total(),
                'page' => $variants->currentPage(),
                'limit' => $variants->perPage(),
                'last_page' => $variants->lastPage(),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.product.failed_to_retrieve_data', $e);
        }
    }

    public function getClientAddresses(Request $request)
{
    try {
        $perPage = $request->input('limit', 20);
        $page = $request->input('page', 1);
        $searchTerm = $request->input('search', '');
        $clientId = $request->input('client_id');

        if (!$clientId || !Client::where('id', $clientId)->exists()) {
            return response()->json([
                'result' => false,
                'message' => __('messages.addresses.invalid_client_id'),
            ], 422);
        }

        $query = Address::with('client')
            ->where('client_id', $clientId);

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('country', 'like', "%$searchTerm%")
                  ->orWhere('city', 'like', "%$searchTerm%")
                  ->orWhere('district', 'like', "%$searchTerm%")
                  ->orWhere('governorate', 'like', "%$searchTerm%");
            });
        }

        $addresses = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'result' => true,
            'message' => __('messages.addresses.addresses_retrieved'),
            'addresses' => AddressResource::collection($addresses),
            'total' => $addresses->total(),
            'page' => $addresses->currentPage(),
            'limit' => $addresses->perPage(),
            'last_page' => $addresses->lastPage(),
        ]);
    } catch (Exception $e) {
        return $this->errorResponse('messages.addresses.failed_to_retrieve_data', $e);
    }
}

}
