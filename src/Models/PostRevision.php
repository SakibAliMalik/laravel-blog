<?php

namespace SakibAliMalik\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostRevision extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'post_id', 'user_id', 'title', 'content',
        'content_json', 'excerpt', 'revision_note',
    ];

    protected $casts = [
        'content_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('blog.user_model'));
    }

    public function restore(): bool
    {
        return $this->post->update([
            'title' => $this->title,
            'content' => $this->content,
            'content_json' => $this->content_json,
            'excerpt' => $this->excerpt,
        ]);
    }
}
