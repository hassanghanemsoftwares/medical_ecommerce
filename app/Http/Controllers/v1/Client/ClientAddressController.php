<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\AddressRequest;
use App\Http\Resources\V1\Client\AddressResource;
use App\Http\Resources\V1\Client\PaginationResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ClientAddressController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:created_at,country,city,district,governorate,is_active,is_default',
                'order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $clientId = $request->user()->id;

            $addresses = Address::with('client')
                ->where('client_id', $clientId)
                ->when($validated['search'] ?? null, function ($q, $search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('country', 'like', "%$search%")
                            ->orWhere('city', 'like', "%$search%")
                            ->orWhere('district', 'like', "%$search%")
                            ->orWhere('governorate', 'like', "%$search%");
                    });
                })
                ->orderBy($validated['sort'] ?? 'created_at', $validated['order'] ?? 'desc')
                ->paginate($validated['per_page'] ?? 10);

            return response()->json([
                'result' => true,
                'message' => __('messages.addresses.retrieved_successfully'),
                'addresses' => AddressResource::collection($addresses),
                'pagination' => new PaginationResource($addresses),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }

    public function show(Address $address, Request $request)
    {
        if ($address->client_id !== $request->user()->id) {
            return response()->json([
                'result' => false,
                'message' => __('messages.addresses.unauthorized_access'),
            ]);
        }

        $address->load('client');

        return response()->json([
            'result' => true,
            'message' => __('messages.addresses.found_successfully'),
            'address' => new AddressResource($address),
        ]);
    }

    public function defaultAddress(Request $request)
    {
        $clientId = $request->user()->id;

        $address = Address::where('client_id', $clientId)
            ->where('is_default', true)
            ->first();

        if (!$address) {
            $address = Address::where('client_id', $clientId)->orderBy('id')->first();
        }

        if (!$address) {
            return response()->json([
                'result' => false,
                'message' => __('messages.addresses.no_default_address_found'),
            ]);
        }

        $address->load('client');

        return response()->json([
            'result' => true,
            'message' => __('messages.addresses.found_successfully'),
            'address' => new AddressResource($address),
        ]);
    }

    public function store(AddressRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['client_id'] = $request->user()->id;

            if (!isset($data['is_default']) || !$data['is_default']) {
                $data['is_default'] = !Address::where('client_id', $data['client_id'])->exists();
            } else {
                Address::where('client_id', $data['client_id'])->update(['is_default' => false]);
            }

            $address = Address::create($data);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.addresses.created_successfully'),
                'address' => new AddressResource($address),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }

    public function update(AddressRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $address = Address::where('id', $id)
                ->where('client_id', $request->user()->id)
                ->firstOrFail();

            $address->fill($request->only([
                'country',
                'city',
                'district',
                'governorate',
                'specifications',
                'latitude',
                'longitude',
            ]));

            if ($request->has('is_active')) {
                $address->is_active = $request->boolean('is_active');
            }

            if ($request->has('is_default')) {
                if ($request->boolean('is_default')) {
                    Address::where('client_id', $address->client_id)
                        ->where('id', '!=', $address->id)
                        ->update(['is_default' => false]);
                    $address->is_default = true;
                } else {
                    $address->is_default = false;

                    $firstAddress = Address::where('client_id', $address->client_id)
                        ->where('id', '!=', $address->id)
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
                'message' => __('messages.addresses.updated_successfully'),
                'address' => new AddressResource($address),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }

    public function destroy(Address $address, Request $request)
    {
        try {
            if ($address->client_id !== $request->user()->id) {
                return response()->json([
                    'result' => false,
                    'message' => __('messages.addresses.unauthorized_access'),
                ]);
            }

            DB::beginTransaction();

            $address->delete();

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.addresses.deleted_successfully'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.error_occurred'), $e);
        }
    }
}
