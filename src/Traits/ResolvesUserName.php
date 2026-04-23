<?php

namespace SakibAliMalik\Blog\Traits;

trait ResolvesUserName
{
    protected function resolveUserName(?object $user): ?string
    {
        if (!$user) {
            return null;
        }

        $attribute = config('blog.user_name_attribute', ['first_name', 'last_name']);

        if (is_array($attribute)) {
            return collect($attribute)
                ->map(fn($field) => $user->{$field} ?? null)
                ->filter()
                ->implode(' ') ?: null;
        }

        return $user->{$attribute} ?? null;
    }
}
