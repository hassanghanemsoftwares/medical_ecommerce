<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\SubscriptionPlanRequest;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Http\Resources\V1\Admin\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name,price,duration',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $plans = SubscriptionPlan::query()
                ->when($validated['search'] ?? null, fn($q, $search) =>
                    $q->where('name', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.subscription_plan.plans_retrieved'),
                'subscription_plans' => SubscriptionPlanResource::collection($plans),
                'pagination' => new PaginationResource($plans),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.subscription_plan.failed_to_retrieve'), $e);
        }
    }

    public function show(SubscriptionPlan $subscriptionPlan)
    {
        return response()->json([
            'result' => true,
            'message' => __('messages.subscription_plan.plan_found'),
            'subscription_plan' => new SubscriptionPlanResource($subscriptionPlan),
        ]);
    }

    public function store(SubscriptionPlanRequest $request)
    {
        try {
            DB::beginTransaction();

            $plan = SubscriptionPlan::create($request->validated());

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.subscription_plan.plan_created'),
                'subscription_plan' => new SubscriptionPlanResource($plan),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.subscription_plan.failed_to_create'), $e);
        }
    }

    public function update(SubscriptionPlanRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $plan = SubscriptionPlan::findOrFail($id);
            $plan->update($request->validated());

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.subscription_plan.plan_updated'),
                'subscription_plan' => new SubscriptionPlanResource($plan),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.subscription_plan.failed_to_update'), $e);
        }
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        try {
            DB::beginTransaction();

            $subscriptionPlan->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.subscription_plan.plan_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.subscription_plan.failed_to_delete'), $e);
        }
    }
}
