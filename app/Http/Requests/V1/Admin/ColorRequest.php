<?php
namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $colorId = $this->route('color');

        return [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:255',
                'unique:colors,name->en,' . ($colorId ?? 'NULL')
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:colors,name->ar,' . ($colorId ?? 'NULL')
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                'unique:colors,code,' . ($colorId ?? 'NULL')
            ],
            'color_season_id' => [
                'required',
                'exists:color_seasons,id'
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
