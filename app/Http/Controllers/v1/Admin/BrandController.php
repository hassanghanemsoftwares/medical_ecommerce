<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\BrandRequest;
use App\Http\Resources\V1\BrandResource;
use App\Http\Resources\V1\PaginationResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name,is_active',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $brands = Brand::query()
                ->when(
                    $validated['search'] ?? null,
                    fn($q, $search) =>
                    $q->where('name', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.brand.brands_retrieved'),
                'brands' => BrandResource::collection($brands),
                'pagination' => new PaginationResource($brands),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('failed_to_retrieve_data', $e);
        }
    }

    public function show(Brand $brand)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.brand.brand_found'),
            'brand' => new BrandResource($brand),
        ]);
    }

    public function store(BrandRequest $request)
    {
        try {
            DB::beginTransaction();

            $brand = Brand::create([
                'name' => $request->input('name'),
                'is_active' => $request->boolean('is_active', true),
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.brand.brand_created'),
                'brand' => new BrandResource($brand),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed_to_create_brand', $e);
        }
    }

    public function update(BrandRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $brand = Brand::findOrFail($id);
            $brand->update([
                'name' => $request->input('name'),
                'is_active' => $request->boolean('is_active', $brand->is_active),
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.brand.brand_updated'),
                'brand' => new BrandResource($brand),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed_to_update_brand', $e);
        }
    }

    public function destroy(Brand $brand)
    {
        try {
            $brand->delete();

            return response()->json([
                'result' => true,
                'message' => __('messages.brand.brand_deleted'),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('failed_to_delete_brand', $e);
        }
    }

    private function errorResponse($messageKey, Exception $e)
    {
        return response()->json([
            'result' => false,
            'message' => __('messages.brand.' . $messageKey),
            'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
        ]);
    }
}
