<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\AddressRequest;
use App\Http\Resources\V1\Admin\AddressResource;
use App\Http\Resources\V1\Admin\PaginationResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,country,city,district,governorate,is_active,is_default',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
                'client_id' => 'nullable|exists:clients,id',
            ]);

            $addresses = Address::with('client')
                ->when($validated['client_id'] ?? null, fn($q, $clientId) => $q->where('client_id', $clientId))
                ->when($validated['search'] ?? null, function ($q, $search) {
                    $q->where('country', 'like', "%$search%")
                        ->orWhere('city', 'like', "%$search%")
                        ->orWhere('district', 'like', "%$search%")
                        ->orWhere('governorate', 'like', "%$search%");
                })
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.addresses.addresses_retrieved'),
                'addresses' => AddressResource::collection($addresses),
                'pagination' => new PaginationResource($addresses),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.addresses.failed_to_retrieve_data'), $e);
        }
    }

    public function show(Address $address)
    {
        $address->load('client');

        return response()->json([
            'result' => true,
            'message' => __('messages.addresses.address_found'),
            'address' => new AddressResource($address),
        ]);
    }

    public function store(AddressRequest $request)
    {
        try {
            DB::beginTransaction();

            $address = Address::create($request->validated());

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.addresses.address_created'),
                'address' => new AddressResource($address),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.addresses.failed_to_create_address'), $e);
        }
    }

    public function update(AddressRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $address = Address::findOrFail($id);

            $address->client_id = $request->input('client_id', $address->client_id);
            $address->country = $request->input('country', $address->country);
            $address->city = $request->input('city', $address->city);
            $address->district = $request->input('district', $address->district);
            $address->governorate = $request->input('governorate', $address->governorate);
            $address->specifications = $request->input('specifications', $address->specifications);
            $address->latitude = $request->input('latitude', $address->latitude);
            $address->longitude = $request->input('longitude', $address->longitude);
            if ($request->has('is_active')) {
                $address->is_active = $request->boolean('is_active');
            }
            if ($request->has('is_default')) {
                Address::where('client_id', $address->client_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
                $address->is_default = $request->boolean('is_default');

                if (!$request->boolean('is_default')) {
                    $firstAddress = Address::where('client_id', $address->client_id)
                        ->orderBy('id')
                        ->first();

                    if ($firstAddress) {
                        $firstAddress->is_default = true;
                        $firstAddress->save();
                    }
                }
            }

            $address->save();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.addresses.address_updated'),
                'address' => new AddressResource($address),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.addresses.failed_to_update_address'), $e);
        }
    }

    public function destroy(Address $address)
    {
        try {
            DB::beginTransaction();

            $address->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.addresses.address_deleted'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.addresses.failed_to_delete_address'), $e);
        }
    }
}
