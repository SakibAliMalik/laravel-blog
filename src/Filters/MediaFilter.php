<?php

namespace SakibAliMalik\Blog\Filters;

class MediaFilter extends Filters
{
    public function search(?string $term): void
    {
        if (!empty($term)) {
            $normalized = '%' . $this->normalizeString($term) . '%';
            $this->builder->where(function ($query) use ($normalized) {
                $query->where('file_name', 'like', $normalized)
                    ->orWhere('caption', 'like', $normalized)
                    ->orWhere('description', 'like', $normalized);
            });
        }
    }

    public function file_type(?string $type): void
    {
        if (!empty($type)) {
            $this->builder->where('file_type', $type);
        }
    }

    public function post_id($postId): void
    {
        if (isset($postId)) {
            $this->builder->where('post_id', $postId);
        }
    }

    public function uploader($value): void
    {
        if (!isset($value)) {
            return;
        }

        if (is_numeric($value)) {
            $this->builder->where('uploaded_by', (int) $value);
            return;
        }

        $term = '%' . $this->normalizeString($value) . '%';
        $this->builder->whereHas('uploader', function ($query) use ($term) {
            $query->where('name', 'like', $term)
                ->orWhere('email', 'like', $term);
        });
    }

    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'file_type' => ['nullable', 'string', 'max:50'],
            'post_id' => ['nullable', 'integer'],
            'uploader' => ['nullable'],
        ];
    }
}
