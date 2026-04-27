<?php

namespace SakibAliMalik\Blog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use SakibAliMalik\Blog\Enums\PostStatusEnum;
use SakibAliMalik\Blog\Models\Post;

class PublishPostJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Post $post,
        public readonly string $scheduledAt,
    ) {}

    public function handle(): void
    {
        $post = $this->post->fresh();

        if (!$post || $post->status !== PostStatusEnum::SCHEDULED) {
            return;
        }

        // published_at was changed — a newer job will handle it
        if ($post->published_at->toDateTimeString() !== $this->scheduledAt) {
            return;
        }

        $post->update([
            'status' => PostStatusEnum::PUBLISHED,
            'pending_job_id' => null,
        ]);
    }
}
