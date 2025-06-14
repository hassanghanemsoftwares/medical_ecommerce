<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\ContactResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\Contact;
use Illuminate\Http\Request;
use Exception;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name,email,is_view',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $contacts = Contact::query()
                ->when($validated['search'] ?? null, function ($q, $search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('subject', 'like', "%$search%");
                })
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);
            Contact::where('is_view', false)->update(['is_view' => true]);

            return response()->json([
                'result' => true,
                'message' => __('messages.contact.contacts_retrieved'),
                'contacts' => ContactResource::collection($contacts),
                'pagination' => new PaginationResource($contacts),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse( __('messages.general.failed_to_retrieve_data'), $e);
        }
    }
}
