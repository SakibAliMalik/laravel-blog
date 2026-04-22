<?php

namespace SakibAliMalik\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['post_id', 'visitor_id', 'ip_address', 'user_agent'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
