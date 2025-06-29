<?php
namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\LearningVideoResource;
use App\Http\Resources\V1\Client\PaginationResource;
use App\Models\LearningVideo;
use Illuminate\Http\Request;
use Exception;

class ClientLearningVideoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,title',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $learningVideos = LearningVideo::when($validated['search'] ?? null, fn($q, $search) =>
                    $q->where('title', 'like', "%$search%")
                      ->orWhere('description', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.learning_video.learning_videos_retrieved'),
                'learning_videos' => LearningVideoResource::collection($learningVideos),
                'pagination' => new PaginationResource($learningVideos),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse( __('messages.learning_video.failed_to_retrieve_data'), $e);
        }
    }
}
