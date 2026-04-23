<?php

namespace SakibAliMalik\Blog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SakibAliMalik\Blog\Traits\ResolvesUserName;

class MediaResource extends JsonResource
{
    use ResolvesUserName;
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_formatted' => $this->size_formatted,
            'width' => $this->width,
            'height' => $this->height,
            'url' => $this->url,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'description' => $this->description,
            'post_id' => $this->post_id,
            'uploader' => $this->whenLoaded('uploader', fn() => [
                'id' => $this->uploader?->id,
                'name' => $this->resolveUserName($this->uploader),
                'email' => $this->uploader?->email,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
