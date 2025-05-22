<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id'    => ['required', 'exists:variants,id'],
            'warehouse_id'  => ['required', 'exists:warehouses,id'],
            'shelf_id'      => ['nullable', 'exists:shelves,id'],

            // Only allow manual or damage adjustments for manual admin API
            'type'          => ['required', 'in:manual,damage'],

            'quantity'      => ['required', 'integer', 'min:1'],

            'direction'     => ['required', 'in:increase,decrease'],

            'cost_per_item' => ['nullable', 'numeric', 'min:0'],
            'reason'        => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => __('messages.stock_adjustment.warehouse_required'),
            'shelf_id.required' => __('messages.stock_adjustment.shelf_required'),
            'quantity.required' => __('messages.stock_adjustment.quantity_required'),
            'cost_per_item.required' => __('messages.stock_adjustment.cost_required'),
            'direction.required' => __('messages.stock_adjustment.direction_required'),
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
            'request_data' => $this->all()
        ], 200));
    }
}
