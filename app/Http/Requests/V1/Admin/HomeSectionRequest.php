<?php
namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class HomeSectionRequest extends FormRequest
{
    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'type' => ['required', Rule::in(['banner', 'about_us', 'team', 'product_section'])],
            'title' => ['nullable', 'string'],
            'arrangement' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ];

        switch ($type) {
            case 'banner':
                $rules['content'] = ['required', 'array'];
                $rules['content.*.image_url'] = ['required', 'url'];
                $rules['content.*.link'] = ['nullable', 'url'];
                break;

            case 'about_us':
                $rules['content.title'] = ['required', 'string'];
                $rules['content.text'] = ['required', 'string'];
                $rules['content.description'] = ['nullable', 'string'];
                break;

            case 'team':
                $rules['content.members'] = ['required', 'array'];
                $rules['content.members.*.name'] = ['required', 'string'];
                $rules['content.members.*.image'] = ['required', 'url'];
                $rules['content.members.*.occupation'] = ['required', 'string'];
                break;

            case 'product_section':
                $rules['content.title'] = ['required', 'string'];
                $rules['content.products'] = ['required', 'array'];
                $rules['content.products.*.product_id'] = ['required', 'exists:products,id'];
                $rules['content.products.*.arrangement'] = ['required', 'integer'];
                break;
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }
}
