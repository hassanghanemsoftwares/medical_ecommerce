<?php
namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ShelfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shelfId = $this->route('shelf');

        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:shelves,name,' . ($shelfId ?? 'NULL')
            ],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => __('messages.shelf.warehouse_id_required'),
            'warehouse_id.exists' => __('messages.shelf.warehouse_id_exists'),
            'name.required' => __('messages.shelf.name_required'),
            'name.string' => __('messages.shelf.name_string'),
            'name.max' => __('messages.shelf.name_max'),
            'name.unique' => __('messages.shelf.name_unique'),
            'location.string' => __('messages.shelf.location_string'),
            'location.max' => __('messages.shelf.location_max'),
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
