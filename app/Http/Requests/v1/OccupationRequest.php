<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class OccupationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $occupationId = $this->route('occupation');

        return [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:255',
                'unique:occupations,name->en,' . ($occupationId ?: 'NULL'),
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:occupations,name->ar,' . ($occupationId ?: 'NULL'),
            ],
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
