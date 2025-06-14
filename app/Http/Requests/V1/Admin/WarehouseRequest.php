<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:warehouses,name,' . ($warehouseId ?? 'NULL')
            ],
            'location' => [
                'nullable',
                'string',
                'max:255'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.warehouse.name_required'),
            'name.string' => __('messages.warehouse.name_string'),
            'name.max' => __('messages.warehouse.name_max'),
            'name.unique' => __('messages.warehouse.name_unique'),
            'location.string' => __('messages.warehouse.location_string'),
            'location.max' => __('messages.warehouse.location_max'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 200));
    }
}
