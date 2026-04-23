<?php

namespace SakibAliMalik\Blog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SakibAliMalik\Blog\Traits\ResolvesUserName;

class PostResource extends JsonResource
{
    use ResolvesUserName;
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'content_json' => $this->content_json,
            'featured_image' => $this->featured_image,
            'status' => $this->status,
            'read_time' => $this->read_time,
            'reading_time_text' => $this->reading_time_text,
            'published_at' => $this->published_at,
            'views_count' => $this->views_count,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'og_image' => $this->og_image,
            'canonical_url' => $this->canonical_url,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'author' => $this->whenLoaded('author', fn() => [
                'id' => $this->author?->id,
                'name' => $this->resolveUserName($this->author),
                'email' => $this->author?->email,
            ]),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
