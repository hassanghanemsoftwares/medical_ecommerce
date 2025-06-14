<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;


class ProductVariantController extends Controller
{
    public function destroy(Variant $productVariant)
    {
        try {
            DB::beginTransaction();

            $productVariant->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.product.update_success'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.product.update_error'), $e);
        }
    }
}
