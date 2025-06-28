<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\NewsletterEmailRequest;
use App\Models\NewsletterEmail;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ClientNewsletterController extends Controller
{
    public function subscribe(NewsletterEmailRequest $request)
    {
        try {

            NewsletterEmail::create([
                'email' => $request->email,
                'is_active' => true,
                'subscribed_at' => Carbon::now(),
            ]);

            return response()->json([
                'result' => true,

                'message' => 'Subscribed successfully!',

            ], 201);
    } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
