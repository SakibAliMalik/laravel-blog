<?php

namespace SakibAliMalik\Blog\Filters;

class TagFilter extends Filters
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

    public function color(?string $color): void
    {
        if (!empty($color)) {
            $this->builder->where('color', $color);
        }
    }

    public function min_posts(?int $count): void
    {
        if ($count !== null) {
            $this->builder->has('posts', '>=', $count);
        }
    }

    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'min_posts' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
