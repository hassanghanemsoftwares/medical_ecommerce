<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class SizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sizeId = $this->route('size');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:sizes,name,' . ($sizeId ?? 'NULL')
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
