<?php

namespace SakibAliMalik\Blog\Enums;

enum PostStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case SCHEDULED = 'scheduled';
    case ARCHIVED = 'archived';

    public static function values(): array
    {
        return array_map(static fn(self $s) => $s->value, self::cases());
    }
}
