<?php
namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme_color1' => 'nullable|string|max:7', // e.g. #324057
            'theme_color2' => 'nullable|string|max:7', // e.g. #EEABAD
            'theme_color3' => 'nullable|string|max:7', // e.g. #EDCFCA
            'theme_color4' => 'nullable|string|max:7', // e.g. #A1B6D8
            'delivery_charge' => 'nullable|numeric|min:0',
            'min_stock_alert' => 'nullable|integer|min:1',
            'store_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'store_address' => 'nullable|string|max:255',
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
