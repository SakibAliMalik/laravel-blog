<?php

namespace SakibAliMalik\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use SakibAliMalik\Blog\Traits\CrudTrait;

class Tag extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'color'];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag): void {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tags')->withTimestamps();
    }

    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->withCount('posts')->orderByDesc('posts_count')->limit($limit);
    }
}
