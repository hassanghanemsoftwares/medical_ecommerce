<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class TagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tagId = $this->route('tag');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:tags,name,' . ($tagId ?? 'NULL'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.tag.name_required'),
            'name.string' => __('messages.tag.name_string'),
            'name.max' => __('messages.tag.name_max'),
            'name.unique' => __('messages.tag.name_unique'),
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
