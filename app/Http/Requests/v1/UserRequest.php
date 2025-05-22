<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->route('user');
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $userId,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
            'is_active' => 'nullable|boolean',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.name')]),
            'email.required' => __('messages.validation.required', ['attribute' => __('messages.validation.attributes.email')]),
            'password.min' => __('messages.validation.min.string', ['attribute' => __('messages.validation.attributes.password'), 'min' => 8]),
            'role.exists' => __('messages.validation.exists', ['attribute' => __('messages.validation.attributes.role')]),
            'permissions.*.exists' => __('messages.validation.exists', ['attribute' => __('messages.validation.attributes.permissions')]),
            'is_active.boolean' => __('messages.validation.boolean', ['attribute' => __('messages.validation.attributes.is_active')]),
        ];
    }
}
