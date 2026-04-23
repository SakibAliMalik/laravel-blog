<?php

namespace SakibAliMalik\Blog\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use SakibAliMalik\Blog\Models\Post;

class BulkPostActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['publish', 'unpublish', 'archive', 'delete'])],
            'post_ids' => ['required', 'array', 'min:1'],
            'post_ids.*' => ['integer', Rule::exists(Post::class, 'id')],
        ];
    }
}
