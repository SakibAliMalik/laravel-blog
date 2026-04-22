<?php

namespace SakibAliMalik\Blog\Filters;

class CategoryFilter extends Filters
{
    public function search(?string $term): void
    {
        if (!empty($term)) {
            $normalized = '%' . $this->normalizeString($term) . '%';
            $this->builder->where(function ($query) use ($normalized) {
                $query->where('name', 'like', $normalized)
                    ->orWhere('slug', 'like', $normalized)
                    ->orWhere('description', 'like', $normalized);
            });
        }
    }

    public function parent_id($value): void
    {
        if (isset($value)) {
            $this->builder->where('parent_id', $value);
        }
    }

    public function root_only(?bool $flag): void
    {
        if ($flag === true) {
            $this->builder->whereNull('parent_id');
        }
    }

    public function has_posts(?bool $flag): void
    {
        if ($flag === null) {
            return;
        }

        if ($flag) {
            $this->builder->whereHas('posts');
        } else {
            $this->builder->doesntHave('posts');
        }
    }

    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'root_only' => ['nullable', 'boolean'],
            'has_posts' => ['nullable', 'boolean'],
        ];
    }
}
