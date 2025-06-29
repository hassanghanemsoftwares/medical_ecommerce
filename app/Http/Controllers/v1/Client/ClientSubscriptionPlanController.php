<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\SubscriptionPlanRequest;
use App\Http\Resources\V1\Client\PaginationResource;
use App\Http\Resources\V1\Client\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ClientSubscriptionPlanController extends Controller
{
    public function index(Request $request)
    {
        try {
             $plans = SubscriptionPlan::where("is_active",true)->orderBy("id","asc")->get();

            return response()->json([
                'result' => true,
                'message' => __('messages.subscription_plan.plans_retrieved'),
                'subscription_plans' => SubscriptionPlanResource::collection($plans),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.subscription_plan.failed_to_retrieve'), $e);
        }
    }
}
