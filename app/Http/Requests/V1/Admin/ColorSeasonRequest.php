<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ColorSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $colorSeasonId = $this->route('color_season');

        return [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:255',
                'unique:color_seasons,name->en,' . ($colorSeasonId ? $colorSeasonId : 'NULL') 
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:color_seasons,name->ar,' . ($colorSeasonId ? $colorSeasonId : 'NULL') 
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
