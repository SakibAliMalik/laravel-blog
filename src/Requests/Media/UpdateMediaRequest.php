<?php

namespace SakibAliMalik\Blog\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
        ];
    }

    public function payload(): array
    {
        return array_filter(
            $this->safe()->only(['alt_text', 'caption', 'description', 'post_id']),
            static fn($value) => !is_null($value)
        );
    }
}
