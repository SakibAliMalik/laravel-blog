<?php

namespace SakibAliMalik\Blog\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('categories', 'slug')->ignore($categoryId)],
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
        return array_filter(
            $this->safe()->only([
                'name', 'slug', 'description', 'parent_id',
                'order_position', 'meta_title', 'meta_description', 'icon', 'color',
            ]),
            static fn($value) => !is_null($value)
        );
    }
}
