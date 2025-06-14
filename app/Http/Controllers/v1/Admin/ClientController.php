<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ClientRequest;
use App\Http\Resources\V1\Admin\ClientResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,name,email',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $clients = Client::with('occupation')
                ->when(
                    $validated['search'] ?? null,
                    fn($q, $search) =>
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%")
                )
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.client.clients_retrieved'),
                'clients' => ClientResource::collection($clients),
                'pagination' => new PaginationResource($clients),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse( __('messages.client.failed_to_retrieve_data'), $e);
        }
    }

    public function show(Client $client)
    {
        $client->load('occupation');

        return response()->json([
            'result' => true,
            'message' => __('messages.client.client_found'),
            'client' => new ClientResource($client),
        ]);
    }

    public function store(ClientRequest $request)
    {
        try {
            DB::beginTransaction();

            $client = Client::create($request->validated());

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.client.client_created'),
                'client' => new ClientResource($client),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __('messages.client.failed_to_create_client'), $e);
        }
    }

    public function update(ClientRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $client = Client::findOrFail($id);

            // Manually assign fields similar to UserController update
            $client->name = $request->input('name', $client->name);
            $client->gender = $request->input('gender', $client->gender);
            $client->birthdate = $request->input('birthdate', $client->birthdate);
            $client->occupation_id = $request->input('occupation_id', $client->occupation_id);
            $client->phone = $request->input('phone', $client->phone);
            $client->email = $request->input('email', $client->email);
            if ($request->has('is_active')) {
                $client->is_active = $request->boolean('is_active');
            }

            $client->save();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.client.client_updated'),
                'client' => new ClientResource($client),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __('messages.client.failed_to_update_client'), $e);
        }
    }


    public function destroy(Client $client)
    {
        try {
            DB::beginTransaction();

            $client->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.client.client_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse( __('messages.client.failed_to_delete_client'), $e);
        }
    }
}
