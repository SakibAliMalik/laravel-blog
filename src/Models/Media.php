<?php

namespace SakibAliMalik\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use SakibAliMalik\Blog\Traits\CrudTrait;

class Media extends Model
{
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'file_name', 'file_path', 'file_type', 'mime_type',
        'size', 'width', 'height', 'alt_text', 'caption',
        'description', 'uploaded_by', 'post_id',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    protected $appends = ['url', 'size_formatted'];

    public function getTable(): string
    {
        return config('blog.table_prefix', '') . 'media';
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(config('blog.user_model'), 'uploaded_by');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk(config('blog.storage_disk', 'public'))->url($this->file_path);
    }

    public function getSizeFormattedAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes > 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    public function scopeImages($query)
    {
        return $query->where('file_type', 'image');
    }

    public function scopeByPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    public function deleteFile(): bool
    {
        $disk = Storage::disk(config('blog.storage_disk', 'public'));

        if ($disk->exists($this->file_path)) {
            return $disk->delete($this->file_path);
        }

        return false;
    }
}
