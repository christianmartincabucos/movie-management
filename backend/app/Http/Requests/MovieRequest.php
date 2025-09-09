<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovieRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date_added' => 'required|date',
            'video_file' => 'required|file|mimes:mp4,mov,avi,wmv|max:10240', // Max 10MB
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'The title is required.',
            'description.required' => 'The description is required.',
            'date_added.required' => 'The date added is required.',
            'video_file.required' => 'The video file is required.',
            'video_file.mimes' => 'The video file must be a file of type: mp4, mov, avi, wmv.',
            'video_file.max' => 'The video file may not be greater than 10MB.',
        ];
    }
}