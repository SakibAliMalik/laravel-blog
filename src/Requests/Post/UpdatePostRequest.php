<?php

namespace SakibAliMalik\Blog\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use SakibAliMalik\Blog\Models\Category;
use SakibAliMalik\Blog\Models\Post;
use SakibAliMalik\Blog\Models\Tag;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('content_json') && is_string($this->content_json)) {
            $decoded = json_decode($this->content_json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['content_json' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        $postId = $this->route('id');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique(Post::class, 'slug')->ignore($postId)],
            'content' => ['sometimes', 'string'],
            'content_json' => ['nullable', 'array'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'scheduled', 'archived'])],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', Rule::exists(Tag::class, 'id')],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function payload(): array
    {
        $data = $this->safe()->only([
            'title', 'slug', 'content', 'content_json', 'excerpt', 'featured_image',
            'category_id', 'status', 'published_at',
            'meta_title', 'meta_description', 'meta_keywords', 'og_image', 'canonical_url',
        ]);

        if (($data['status'] ?? null) === 'published') {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        if (!empty($data['published_at']) && $timezone = config('blog.input_timezone')) {
            $data['published_at'] = \Carbon\Carbon::parse($data['published_at'], $timezone)->utc();
        }

        return array_filter($data, static fn($value) => !is_null($value));
    }
}
