<?php

namespace App\Http\Requests\V1\Admin;

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
            'quantity'      => ['required', 'integer', 'min:1'],
            'direction'     => ['required', 'in:increase,decrease'],
            'cost_per_item' => ['nullable', 'numeric', 'min:0'],
            'reason'        => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'variant_id.required' => __('messages.stock_adjustment.variant_required'),
            'variant_id.exists' => __('messages.stock_adjustment.variant_exists'),
            'warehouse_id.required' => __('messages.stock_adjustment.warehouse_required'),
            'warehouse_id.exists' => __('messages.stock_adjustment.warehouse_exists'),
            'shelf_id.exists' => __('messages.stock_adjustment.shelf_exists'),
            'quantity.required' => __('messages.stock_adjustment.quantity_required'),
            'quantity.integer' => __('messages.stock_adjustment.quantity_integer'),
            'quantity.min' => __('messages.stock_adjustment.quantity_min'),
            'direction.required' => __('messages.stock_adjustment.direction_required'),
            'direction.in' => __('messages.stock_adjustment.direction_in'),
            'cost_per_item.numeric' => __('messages.stock_adjustment.cost_numeric'),
            'cost_per_item.min' => __('messages.stock_adjustment.cost_min'),
            'reason.string' => __('messages.stock_adjustment.reason_string'),
            'reason.max' => __('messages.stock_adjustment.reason_max'),
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
