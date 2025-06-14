<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class LearningVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $learningVideoId = $this->route('learning_video');

        return [
            'title' => 'required|array',
            'title.en' => [
                'required',
                'string',
                'max:191',
                'unique:learning_videos,title->en,' . ($learningVideoId ? $learningVideoId : 'NULL')
            ],
            'title.ar' => [
                'required',
                'string',
                'max:191',
                'unique:learning_videos,title->ar,' . ($learningVideoId ? $learningVideoId : 'NULL')
            ],
            'description' => 'required|array',
            'description.en' => [
                'required',
                'string',
            ],
            'description.ar' => [
                'required',
                'string',
            ],
            'video' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('messages.learning_video.title_required'),
            'title.en.required' => __('messages.learning_video.title_en_required'),
            'title.en.unique' => __('messages.learning_video.title_en_unique'),
            'title.en.max' => __('messages.learning_video.title_en_max'),
            'title.ar.required' => __('messages.learning_video.title_ar_required'),
            'title.ar.unique' => __('messages.learning_video.title_ar_unique'),
            'title.ar.max' => __('messages.learning_video.title_ar_max'),

            'description.required' => __('messages.learning_video.description_required'),
            'description.en.required' => __('messages.learning_video.description_en_required'),
            'description.ar.required' => __('messages.learning_video.description_ar_required'),

            'video.required' => __('messages.learning_video.video_required'),
            'video.string' => __('messages.learning_video.video_string'),
            'video.max' => __('messages.learning_video.video_max'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'result' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 200));
    }
}
