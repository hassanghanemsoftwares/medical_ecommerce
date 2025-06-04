<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\CategoryRequest;
use App\Http\Resources\V1\Admin\CategoryResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name,arrangement,is_active',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $categories = Category::query()
                ->when(
                    $validated['search'] ?? null,
                    fn($q, $search) =>
                    $q->where('name->en', 'like', "%$search%")
                        ->orWhere('name->ar', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.category.categories_retrieved'),
                'categories' => CategoryResource::collection($categories),
                'pagination' => new PaginationResource($categories),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __('messages.user.failed_to_retrieve_data'),
                'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
            ]);
        }
    }

    public function show(Category $category)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.category.category_found'),
            'category' => new CategoryResource($category),
        ]);
    }

    public function store(CategoryRequest $request)
    {
        try {
            DB::beginTransaction();

            $category = new Category([
                'name' => $request->input('name'),
                'is_active' => $request->boolean('is_active', true),
                'arrangement' => Category::getNextArrangement(),
            ]);

            if ($request->hasFile('image')) {
                $category->image = Category::storeImage($request->file('image'));
            }

            $category->save();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.category.category_created'),
                'category' => new CategoryResource($category),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.category.failed_to_create_category', $e);
        }
    }

    public function update(CategoryRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $category = Category::findOrFail($id);

            $category->fill([
                'name' => $request->input('name'),
                'is_active' => $request->boolean('is_active', $category->is_active),
                'arrangement' => Category::updateArrangement($category, $request->input('arrangement', $category->arrangement)),
            ]);

            if ($request->hasFile('image')) {
                Category::deleteImage($category->getRawOriginal('image'));
                $category->image = Category::storeImage($request->file('image'));
            }

            $category->save();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.category.category_updated'),
                'category' => new CategoryResource($category),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.category.failed_to_update_category', $e);
        }
    }

    public function destroy(Category $category)
    {
        try {
            DB::beginTransaction();

            Category::rearrangeAfterDelete($category->arrangement);

            Category::deleteImage($category->getRawOriginal('image'));

            $category->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.category.category_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('messages.category.failed_to_delete_category', $e);
        }
    }


}
