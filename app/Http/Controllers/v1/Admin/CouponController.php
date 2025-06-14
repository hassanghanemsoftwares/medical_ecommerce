<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\CouponRequest;
use App\Http\Resources\V1\Admin\CouponResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,code,value,type,status,coupon_type,valid_from,valid_to,usage_count,usage_limit',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $coupons = Coupon::when(
                $validated['search'] ?? null,
                fn($q, $search) =>
                $q->where('code', 'like', "%$search%")
            )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.coupon.coupons_retrieved'),
                'coupons' => CouponResource::collection($coupons),
                'pagination' => new PaginationResource($coupons),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse( __('messages.coupon.failed_to_retrieve_data'), $e);
        }
    }

    public function show(Coupon $coupon)
    {
        $coupon->load('coupon');

        return response()->json([
            'result' => true,
            'message' => __('messages.coupon.coupon_found'),
            'coupon' => new CouponResource($coupon),
        ]);
    }

    public function store(CouponRequest $request)
    {
        try {
            DB::beginTransaction();

            $coupon = Coupon::create($request->validated());

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.coupon.coupon_created'),
                'coupon' => new CouponResource($coupon),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __('messages.coupon.failed_to_create_coupon'), $e);
        }
    }

    public function update(CouponRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $coupon = Coupon::findOrFail($id);
            $coupon->update($request->validated());

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.coupon.coupon_updated'),
                'coupon' => new CouponResource($coupon),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __('messages.coupon.failed_to_update_coupon'), $e);
        }
    }

    public function destroy(Coupon $coupon)
    {
        try {
            $coupon->delete();

            return response()->json([
                'result' => true,
                'message' => __('messages.coupon.coupon_deleted'),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse( __('messages.coupon.failed_to_delete_coupon'), $e);
        }
    }
    public function checkUsability(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|exists:coupons,code',
        ]);

        $coupon = Coupon::where('code', $validated['code'])->first();
        [$canUse, $reason] = $coupon->canBeUsed();

        if (!$canUse) {
            return response()->json([
                'result' => false,
                'message' =>  $reason,
            ], 200);
        }

        return response()->json([
            'result' => true,
            'message' => __('messages.coupon.can_be_used'),
            'coupon' => new CouponResource($coupon),
        ]);
    }
}
