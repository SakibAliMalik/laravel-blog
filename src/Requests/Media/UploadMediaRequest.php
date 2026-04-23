<?php

namespace SakibAliMalik\Blog\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use SakibAliMalik\Blog\Models\Post;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'post_id' => ['nullable', 'integer', Rule::exists(Post::class, 'id')],
        ];
    }

    public function meta(): array
    {
        return $this->safe()->only(['alt_text', 'caption', 'description', 'post_id']);
    }
}
