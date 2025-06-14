<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Http\Resources\V1\Admin\ActivityLogResource as V1ActivityLogResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use Exception;
use Illuminate\Support\Carbon;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'log_name' => 'nullable|string|max:100',
                'causer_type' => 'nullable|string|max:100',
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from',
                'sort' => 'nullable|in:created_at,log_name,causer_type,subject_type',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Activity::with(['causer', 'subject']);

            if (!empty($validated['search'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('description', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('subject_type', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('causer_type', 'like', '%' . $validated['search'] . '%');
                });
            }

            if (!empty($validated['log_name'])) {
                $query->where('log_name', $validated['log_name']);
            }

            if (!empty($validated['causer_type'])) {
                $query->where('causer_type', $validated['causer_type']);
            }

            if (!empty($validated['from'])) {
                $query->whereDate('created_at', '>=', Carbon::parse($validated['from']));
            }

            if (!empty($validated['to'])) {
                $query->whereDate('created_at', '<=', Carbon::parse($validated['to']));
            }

            $sortBy = $validated['sort'] ?? 'created_at';
            $sortOrder = $validated['order'] ?? 'desc';
            $perPage = $validated['per_page'] ?? 15;

            $logs = $query->orderBy($sortBy, $sortOrder)->paginate($perPage);

            return response()->json([
                'result' => true,
                'message' => __('messages.retrieved_successfully'),
                'logs' => V1ActivityLogResource::collection($logs),
                'pagination' => new PaginationResource($logs),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse( __('messages.logs.failed_to_retrieve'), $e);
        }
    }
}
