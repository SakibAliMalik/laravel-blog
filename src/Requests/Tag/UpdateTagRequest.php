<?php

namespace SakibAliMalik\Blog\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use SakibAliMalik\Blog\Models\Tag;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tagId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:100', Rule::unique(Tag::class, 'name')->ignore($tagId)],
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique(Tag::class, 'slug')->ignore($tagId)],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function payload(): array
    {
        return array_filter(
            $this->safe()->only(['name', 'slug', 'description', 'color']),
            static fn($value) => !is_null($value)
        );
    }
}
