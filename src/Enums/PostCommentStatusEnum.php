<?php

namespace SakibAliMalik\Blog\Enums;

enum PostCommentStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case SPAM = 'spam';
    case TRASH = 'trash';

    public static function values(): array
    {
        return array_map(static fn(self $s) => $s->value, self::cases());
    }
}
