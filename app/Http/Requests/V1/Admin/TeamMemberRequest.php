<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class TeamMemberRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $teamMemberId = $this->route('team_member');

        $rules = [
            'name' => 'required|array',
            'name.en' => [
                'required',
                'string',
                'max:255',
                'unique:team_members,name->en,' . ($teamMemberId ? $teamMemberId : 'NULL'),
            ],
            'name.ar' => [
                'required',
                'string',
                'max:255',
                'unique:team_members,name->ar,' . ($teamMemberId ? $teamMemberId : 'NULL'),
            ],
            'occupation' => 'required|array',
            'occupation.en' => ['required', 'string', 'max:255'],
            'occupation.ar' => ['required', 'string', 'max:255'],
            'arrangement' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];

        $imageRule = [
            $this->isMethod('post') ? 'required' : 'nullable',
            'image',
            'mimes:jpeg,jpg,png,gif',
            'max:2048',
            'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ];

        $rules['image'] = $imageRule;

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => __('messages.team_member.name_required'),
            'name.en.required' => __('messages.team_member.name_en_required'),
            'name.en.unique' => __('messages.team_member.name_en_unique'),
            'name.ar.required' => __('messages.team_member.name_ar_required'),
            'name.ar.unique' => __('messages.team_member.name_ar_unique'),
            'occupation.required' => __('messages.team_member.occupation_required'),
            'occupation.en.required' => __('messages.team_member.occupation_en_required'),
            'occupation.ar.required' => __('messages.team_member.occupation_ar_required'),
            'image.required' => __('messages.team_member.image_required'),
            'image.mimes' => __('messages.team_member.image_mimes'),
            'image.max' => __('messages.team_member.image_max_size'),
            'image.dimensions' => __('messages.team_member.image_dimensions'),
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
