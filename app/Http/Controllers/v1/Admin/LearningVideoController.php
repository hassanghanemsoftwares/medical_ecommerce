<?php
namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LearningVideoRequest;
use App\Http\Resources\V1\LearningVideoResource;
use App\Http\Resources\V1\PaginationResource;
use App\Models\LearningVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class LearningVideoController extends Controller
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
            return $this->errorResponse('failed_to_retrieve_data', $e);
        }
    }

    public function show(LearningVideo $learningVideo)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.learning_video.learning_video_found'),
            'learning_video' => new LearningVideoResource($learningVideo),
        ]);
    }

    public function store(LearningVideoRequest $request)
    {
        try {
            DB::beginTransaction();

            $learningVideo = LearningVideo::create($request->only(['title', 'description', 'video']));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.learning_video.learning_video_created'),
                'learning_video' => new LearningVideoResource($learningVideo),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed_to_create_learning_video', $e);
        }
    }

    public function update(LearningVideoRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $learningVideo = LearningVideo::findOrFail($id);
            $learningVideo->update($request->only(['title', 'description', 'video']));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.learning_video.learning_video_updated'),
                'learning_video' => new LearningVideoResource($learningVideo),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed_to_update_learning_video', $e);
        }
    }

    public function destroy(LearningVideo $learningVideo)
    {
        try {
            DB::beginTransaction();

            $learningVideo->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.learning_video.learning_video_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed_to_delete_learning_video', $e);
        }
    }

    private function errorResponse($messageKey, Exception $e)
    {
        return response()->json([
            'result' => false,
            'message' => __('messages.learning_video.' . $messageKey),
            'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
        ]);
    }
}
