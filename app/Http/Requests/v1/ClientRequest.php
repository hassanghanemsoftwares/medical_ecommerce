<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client');

        return [
            'name' => 'required|string|max:100',
            'gender' => 'nullable|in:male,female,other',
            'birthdate' => 'nullable|date',
            'occupation_id' => 'required|exists:occupations,id',
            'phone' => 'nullable|string|max:20|unique:clients,phone,' . ($clientId ?? 'NULL'),
            'email' => 'required|email|max:150|unique:clients,email,' . ($clientId ?? 'NULL'),
            'is_active' => 'boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
            'request_data' => $this->all(),
        ], 200));
    }
}
