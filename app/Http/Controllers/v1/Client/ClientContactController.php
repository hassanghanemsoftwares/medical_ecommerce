<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\ContactRequest;
use App\Mail\ContactMail;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Mail;

class ClientContactController extends Controller
{

    public function store(ContactRequest $request)
    {
        try {
            DB::beginTransaction();

            Contact::create($request->validated());
            Mail::to(config('mail.mailers.smtp.username'))->send(new ContactMail($request->validated()));
            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.contact.submited_successfully'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
