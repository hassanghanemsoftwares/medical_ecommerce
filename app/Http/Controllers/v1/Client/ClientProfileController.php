<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\ProfileRequest;
use App\Http\Resources\V1\Client\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class ClientProfileController extends Controller
{
    public function getCurrentUser(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user->is_active) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.account_inactive'),
                ]);
            }

            return response()->json([
                'result' => true,
                'message' => __('messages.profile_fetched_successfully'),
                'client' => new ClientResource($user),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'result' => true,
                'message' => __('messages.successfully_logged_out'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }

    public function update(ProfileRequest $request)
    {
        try {
            $client = Client::find($request->user()->id);

            if (!$client) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.client_not_found'),
                ]);
            }

            $client->update([
                'name'          => $request->name,
                'gender'        => $request->gender,
                'birthdate'     => $request->birthdate,
                'occupation_id' => $request->occupation_id,
                'phone'         => $request->phone,
            ]);

            $client->load('occupation');

            return response()->json([
                'result' => true,
                'message' => __('messages.profile_updated_successfully'),
                'client' => (new ClientResource($client))->toArray($request),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
