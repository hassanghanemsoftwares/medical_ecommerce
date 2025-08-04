<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Http\Resources\V1\Admin\ReviewResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,rating,is_active,client_name,product_name',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $reviews = Review::query()
                ->with(['client', 'product'])
                ->leftJoin('clients', 'reviews.client_id', '=', 'clients.id')
                ->leftJoin('products', 'reviews.product_id', '=', 'products.id')
                ->when($validated['search'] ?? null, function ($query, $search) {
                    $query->where('reviews.comment', 'like', "%{$search}%");
                })
                ->when(
                    ($validated['sort'] ?? null) === 'client_name',
                    fn($q) => $q->orderBy('clients.name', $validated['order'] ?? 'asc')
                )
                ->when(
                    ($validated['sort'] ?? null) === 'product_name',
                    fn($q) => $q->orderBy('products.name', $validated['order'] ?? 'asc')
                )
                ->when(
                    !in_array($validated['sort'] ?? '', ['client_name', 'product_name']),
                    fn($q) => $q->orderBy($validated['sort'] ?? 'reviews.created_at', $validated['order'] ?? 'desc')
                )

                ->select('reviews.*')
                ->paginate($validated['per_page'] ?? 10);
            Review::where('is_view', false)->update(['is_view' => true]);
            return response()->json([
                'result' => true,
                'message' => __('messages.review.reviews_retrieved'),
                'reviews' => ReviewResource::collection($reviews),
                'pagination' => new PaginationResource($reviews),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.review.failed_to_retrieve_data'), $e);
        }
    }

    public function show(Review $review)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.review.review_found'),
            'review' => new ReviewResource($review->load(['client', 'product'])),
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'is_active' => 'required|boolean',
            ]);

            DB::beginTransaction();

            $review = Review::findOrFail($id);
            $review->update([
                'is_active' => $request->boolean('is_active'),
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.review.review_updated'),
                'review' => new ReviewResource($review),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.review.failed_to_update_review'), $e);
        }
    }
}
