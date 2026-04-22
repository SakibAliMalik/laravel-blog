<?php

namespace SakibAliMalik\Blog\Filters;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use SakibAliMalik\Blog\Enums\PostStatusEnum;

class PostFilter extends Filters
{
    public function search(?string $term): void
    {
        if (!empty($term)) {
            $this->builder->search($this->normalizeString($term));
        }
    }

    public function status(?string $status): void
    {
        if (!empty($status) && in_array($status, PostStatusEnum::values())) {
            $this->builder->{$status}();
        }
    }

    public function category($value): void
    {
        if (!isset($value)) {
            return;
        }

        if (is_numeric($value)) {
            $this->builder->byCategory((int) $value);
            return;
        }

        if (strtolower($value) === 'all') {
            return;
        }

        $slug = $this->normalizeString($value);
        $this->builder->whereHas('category', function ($query) use ($slug) {
            $query->where('slug', 'like', $slug)
                ->orWhere('name', 'like', '%' . $slug . '%');
        });
    }

    public function author($value): void
    {
        if (!isset($value)) {
            return;
        }

        if (is_numeric($value)) {
            $this->builder->byAuthor((int) $value);
            return;
        }

        $term = '%' . $this->normalizeString($value) . '%';
        $this->builder->whereHas('author', function ($query) use ($term) {
            $query->where('name', 'like', $term)
                ->orWhere('email', 'like', $term);
        });
    }

    public function tag($value): void
    {
        if (!isset($value)) {
            return;
        }

        if (is_numeric($value)) {
            $this->builder->whereHas('tags', function ($q) use ($value) {
                $q->where('tags.id', (int) $value);
            });
            return;
        }

        $this->builder->byTag($this->normalizeString($value));
    }

    public function date_from(?string $date): void
    {
        if (!empty($date)) {
            $this->builder->whereDate('created_at', '>=', Carbon::parse($date));
        }
    }

    public function date_to(?string $date): void
    {
        if (!empty($date)) {
            $this->builder->whereDate('created_at', '<=', Carbon::parse($date));
        }
    }

    public function published_from(?string $date): void
    {
        if (!empty($date)) {
            $this->builder->whereDate('published_at', '>=', Carbon::parse($date));
        }
    }

    public function published_to(?string $date): void
    {
        if (!empty($date)) {
            $this->builder->whereDate('published_at', '<=', Carbon::parse($date));
        }
    }

    public function only_trashed(?bool $flag): void
    {
        if ($flag === null) {
            return;
        }

        if ($flag) {
            $this->builder->onlyTrashed();
        } else {
            $this->builder->withoutTrashed();
        }
    }

    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(PostStatusEnum::values())],
            'category' => ['nullable'],
            'author' => ['nullable'],
            'tag' => ['nullable'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:query.date_from'],
            'published_from' => ['nullable', 'date'],
            'published_to' => ['nullable', 'date', 'after_or_equal:query.published_from'],
            'only_trashed' => ['nullable', 'boolean'],
        ];
    }
}
