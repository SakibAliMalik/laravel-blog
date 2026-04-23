<?php

namespace SakibAliMalik\Blog\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use SakibAliMalik\Blog\Models\Tag;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Tag::class, 'name')],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique(Tag::class, 'slug')],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function payload(): array
    {
        return $this->safe()->only(['name', 'slug', 'description', 'color']);
    }
}
