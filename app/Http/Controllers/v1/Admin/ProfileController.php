<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;

use App\Http\Resources\V1\Admin\UserResource as V1UserResource;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{

    public function getCurrentUser(Request $request)
    {
        try {
            $user = $request->user();
            // $user->givePermissionTo(['view-activity-logs']);
            if (!$user->is_active) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.account_inactive')
                ]);
            }

            return response()->json([
                'result' => true,
                'message' => __('messages.otp_verified'),
                'user' => new V1UserResource($user),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $token = $user->currentAccessToken();
            $tokenId = $token instanceof PersonalAccessToken ? $token->id : null;
            DB::table('sessions')->where('token_id', $tokenId)->delete();
            $token->delete();
            return response()->json([
                'result' => true,
                'message' => __('messages.successfully_logged_out')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->letters()->numbers()->symbols()],
        ], [
            'current_password.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.current_password')]),
            'new_password.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.new_password')]),
            'new_password.min' => __('messages.validation.min.string', ['attribute' => __('messages.validation.attributes.new_password'), 'min' => 8]),
            'new_password.confirmed' => __('messages.validation.confirmed', ['attribute' => __('messages.validation.attributes.new_password')]),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.invalid_current_password')
                ]);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'result' => true,
                'message' => __('messages.password_changed_successfully')
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
