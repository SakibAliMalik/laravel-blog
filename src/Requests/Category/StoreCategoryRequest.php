<?php

namespace SakibAliMalik\Blog\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'order_position' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function payload(): array
    {
        return $this->safe()->only([
            'name', 'slug', 'description', 'parent_id',
            'order_position', 'meta_title', 'meta_description', 'icon', 'color',
        ]);
    }
}
