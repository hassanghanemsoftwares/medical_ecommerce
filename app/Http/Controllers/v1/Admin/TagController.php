<?php
namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\TagRequest;
use App\Http\Resources\V1\TagResource;
use App\Http\Resources\V1\PaginationResource;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TagController extends Controller
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

            $tags = Tag::when($validated['search'] ?? null, fn($q, $search) =>
                    $q->where('name', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.tag.tags_retrieved'),
                'tags' => TagResource::collection($tags),
                'pagination' => new PaginationResource($tags),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('failed_to_retrieve_data', $e);
        }
    }

    public function show(Tag $tag)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.tag.tag_found'),
            'tag' => new TagResource($tag),
        ]);
    }

    public function store(TagRequest $request)
    {
        try {
            DB::beginTransaction();

            $tag = Tag::create($request->only(['name']));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.tag.tag_created'),
                'tag' => new TagResource($tag),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed_to_create_tag', $e);
        }
    }

    public function update(TagRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $tag = Tag::findOrFail($id);
            $tag->update($request->only(['name']));

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.tag.tag_updated'),
                'tag' => new TagResource($tag),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed_to_update_tag', $e);
        }
    }

    public function destroy(Tag $tag)
    {
        try {
            DB::beginTransaction();

            $tag->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.tag.tag_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('failed_to_delete_tag', $e);
        }
    }

    private function errorResponse($messageKey, Exception $e)
    {
        return response()->json([
            'result' => false,
            'message' => __('messages.tag.' . $messageKey),
            'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
        ]);
    }
}
