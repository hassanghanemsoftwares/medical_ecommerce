<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\ReviewRequest;
use App\Http\Resources\V1\Client\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Exception;
use Illuminate\Http\Request;

class ClientReviewController extends Controller
{

    public function store(ReviewRequest $request)
    {
        try {
            $clientId = $request->user()->id;
            $review = Review::updateOrCreate(
                [
                    'client_id' => $clientId,
                    'product_id' => $request->product_id,
                ],
                [
                    'rating' => $request->rating,
                    'comment' => $request->comment,
                    'is_active' => true,
                    'is_view' => true,
                ]
            );

            return response()->json([
                'result' => true,
                'message' => $review->wasRecentlyCreated
                    ? __('messages.review.created')
                    : __('messages.review.updated'),
                'review' => new ReviewResource($review),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }


    public function destroy(Request $request, Review $review)
    {
        try {
            $clientId = $request->user()->id;

            if ($review->client_id !== $clientId) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.addresses.unauthorized_access'),
                ], 403);
            }

            $review->delete();

            return response()->json([
                'result' => true,
                'message' => __('messages.review.deleted'),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
