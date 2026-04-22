<?php

namespace SakibAliMalik\Blog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'order_position' => $this->order_position,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'icon' => $this->icon,
            'color' => $this->color,
            'posts_count' => $this->whenCounted('posts'),
            'parent' => $this->whenLoaded('parent', fn() => [
                'id' => $this->parent?->id,
                'name' => $this->parent?->name,
                'slug' => $this->parent?->slug,
            ]),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
        ];
    }
}
