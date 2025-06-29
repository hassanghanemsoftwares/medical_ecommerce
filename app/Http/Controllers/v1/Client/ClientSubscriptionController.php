<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Http\Resources\V1\Admin\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Exception;

class ClientSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,starts_at,ends_at',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $subscriptions = Subscription::with(['client', 'subscription_plan'])
                ->when($validated['search'] ?? null, function ($query, $search) {
                    $query->whereHas(
                        'client',
                        fn($q) =>
                        $q->where('name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%")
                    );
                })
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.subscription.subscriptions_retrieved'),
                'subscriptions' => SubscriptionResource::collection($subscriptions),
                'pagination' => new PaginationResource($subscriptions),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.subscription.failed_to_retrieve'), $e);
        }
    }
    public function update(Request $request, Subscription $subscription)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $subscription->update([
                'is_active' => $validated['is_active'],
            ]);

            return response()->json([
                'result' => true,
                'message' => __('messages.subscription.subscription_updated'),
                'subscription' => new SubscriptionResource($subscription->load(['client', 'plan'])),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.subscription.failed_to_update'), $e);
        }
    }
}
