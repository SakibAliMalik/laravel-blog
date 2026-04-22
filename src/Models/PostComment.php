<?php

namespace SakibAliMalik\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SakibAliMalik\Blog\Enums\PostCommentStatusEnum;

class PostComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id', 'user_id', 'author_name', 'author_email',
        'author_url', 'content', 'status', 'parent_id',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'post_id' => 'integer',
        'user_id' => 'integer',
        'parent_id' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('blog.user_model'));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(PostComment::class, 'parent_id')->orderBy('created_at');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', PostCommentStatusEnum::APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', PostCommentStatusEnum::PENDING);
    }

    public function scopeSpam($query)
    {
        return $query->where('status', PostCommentStatusEnum::SPAM);
    }

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    public function approve(): bool
    {
        return $this->update(['status' => PostCommentStatusEnum::APPROVED]);
    }

    public function markAsSpam(): bool
    {
        return $this->update(['status' => PostCommentStatusEnum::SPAM]);
    }
}
