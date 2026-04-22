<?php

namespace SakibAliMalik\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use SakibAliMalik\Blog\Enums\PostStatusEnum;
use SakibAliMalik\Blog\Traits\CrudTrait;

class Post extends Model
{
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'content_json', 'excerpt', 'featured_image',
        'author_id', 'category_id', 'status', 'published_at',
        'meta_title', 'meta_description', 'meta_keywords',
        'og_image', 'canonical_url', 'views_count', 'read_time',
    ];

    protected $casts = [
        'content_json' => 'array',
        'published_at' => 'datetime',
        'views_count' => 'integer',
        'read_time' => 'integer',
        'status' => PostStatusEnum::class,
    ];

    protected $appends = ['is_published', 'reading_time_text'];

    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            if (empty($post->slug)) {
                $post->slug = static::generateUniqueSlug($post->title);
            }

            if (empty($post->read_time) && !empty($post->content)) {
                $post->read_time = static::calculateReadTime($post->content);
            }

            if (empty($post->excerpt) && !empty($post->content)) {
                $post->excerpt = Str::limit(strip_tags($post->content), 200);
            }

            if (empty($post->meta_title)) {
                $post->meta_title = $post->title;
            }
        });

        static::updating(function (Post $post): void {
            if ($post->isDirty('content')) {
                $post->read_time = static::calculateReadTime($post->content);

                if (empty($post->excerpt)) {
                    $post->excerpt = Str::limit(strip_tags($post->content), 200);
                }
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(config('blog.user_model'), 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags')->withTimestamps();
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class)->orderByDesc('created_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatusEnum::PUBLISHED)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PostStatusEnum::DRAFT);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', PostStatusEnum::SCHEDULED)
            ->where('published_at', '>', now());
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PostStatusEnum::ARCHIVED);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByAuthor(Builder $query, int $authorId): Builder
    {
        return $query->where('author_id', $authorId);
    }

    public function scopeByTag(Builder $query, string $tagSlug): Builder
    {
        return $query->whereHas('tags', function (Builder $builder) use ($tagSlug): void {
            $builder->where('slug', $tagSlug);
        });
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $builder) use ($term): void {
            $builder->where('title', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%");
        });
    }

    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('views_count')->limit($limit);
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status == PostStatusEnum::PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function getReadingTimeTextAttribute(): string
    {
        if (!$this->read_time) {
            return '1 min read';
        }

        return $this->read_time === 1 ? '1 min read' : "{$this->read_time} mins read";
    }

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    public static function calculateReadTime(string $content): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($content)) / 200));
    }

    public function publish(): bool
    {
        return $this->update([
            'status' => PostStatusEnum::PUBLISHED,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    public function unpublish(): bool
    {
        return $this->update(['status' => PostStatusEnum::DRAFT]);
    }

    public function archive(): bool
    {
        return $this->update(['status' => PostStatusEnum::ARCHIVED]);
    }

    public function createRevision(?string $note = null): PostRevision
    {
        return $this->revisions()->create([
            'user_id' => auth()->id() ?? $this->author_id,
            'title' => $this->title,
            'content' => $this->content,
            'content_json' => $this->content_json,
            'excerpt' => $this->excerpt,
            'revision_note' => $note,
        ]);
    }
}
