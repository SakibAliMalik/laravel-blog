<?php

namespace SakibAliMalik\Blog\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use SakibAliMalik\Blog\Enums\PostStatusEnum;
use SakibAliMalik\Blog\Jobs\PublishPostJob;
use SakibAliMalik\Blog\Filters\PostFilter;
use SakibAliMalik\Blog\Models\Post;
use SakibAliMalik\Blog\Requests\Post\BulkPostActionRequest;
use SakibAliMalik\Blog\Requests\Post\StorePostRequest;
use SakibAliMalik\Blog\Requests\Post\UpdatePostRequest;
use SakibAliMalik\Blog\Resources\PostResource;
use SakibAliMalik\Blog\Traits\ApiResponseTrait;
use SakibAliMalik\Blog\Traits\PaginationTrait;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    use ApiResponseTrait;
    use PaginationTrait;

    public function index(PostFilter $postFilter): JsonResponse
    {
        try {
            $query = Post::with(['author', 'category', 'tags', 'media'])
                ->filter($postFilter)
                ->when(!request()->has('sort'), fn($builder) => $builder->reorder()->orderByDesc('created_at'));

            $result = $this->handlePaginationWithResource(
                $query,
                PostResource::class,
                [],
                [],
                request()->boolean('pagination.export', false)
            );

            if (($result['success'] ?? true) === false) {
                return $this->failedApiResponse(
                    $result['message'] ?? 'Something went wrong.',
                    $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                    $result['errors'] ?? []
                );
            }

            return $this->successResponse($result, 'Records retrieved successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Post::class, __FUNCTION__, $th, request()->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $post = Post::with(['author', 'category', 'tags', 'media'])->findOrFail($id);
            return $this->successResponse(new PostResource($post), 'Record fetched successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Post::class, __FUNCTION__, $th, ['id' => $id]);
            return $this->someThingWentWrong($th);
        }
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $post = Post::create(array_merge($request->payload(), [
                'author_id' => $request->user()->id,
            ]));

            if ($request->filled('tags')) {
                $post->tags()->sync($request->input('tags', []));
            }

            if ($post->status === PostStatusEnum::SCHEDULED && $post->published_at) {
                $this->schedulePublishJob($post);
            }

            $post->createRevision('Initial version');
            $post->load(['author', 'category', 'tags', 'media']);
            DB::commit();

            return $this->successResponse(new PostResource($post), 'Created successfully.', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->logsError(static::class, Post::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $post = Post::find($id);

        if (!$post) {
            return $this->failedApiResponse('Post not found.', Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();
        try {
            $payload = $request->payload();

            if (array_key_exists('content', $payload) && $payload['content'] !== $post->content) {
                $post->createRevision('Before update');
            }

            $statusChanged = isset($payload['status']) && $payload['status'] !== $post->status->value;
            $timeChanged = isset($payload['published_at']) && $payload['published_at']->toDateTimeString() !== $post->published_at?->toDateTimeString();

            if ($statusChanged || $timeChanged) {
                $this->cancelPendingJob($post);
            }

            $post->update($payload);

            $updatedPost = $post->fresh();
            if ($updatedPost->status === PostStatusEnum::SCHEDULED && $updatedPost->published_at && ($statusChanged || $timeChanged)) {
                $this->schedulePublishJob($updatedPost);
            }

            if ($request->has('tags')) {
                $post->tags()->sync($request->input('tags', []));
            }

            $post->load(['author', 'category', 'tags', 'media']);
            DB::commit();

            return $this->successResponse(new PostResource($post), 'Updated successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->logsError(static::class, Post::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            Post::findOrFail($id)->delete();
            return $this->successMessageApiResponse('Deleted successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Post::class, __FUNCTION__, $th, ['id' => $id]);
            return $this->someThingWentWrong($th);
        }
    }

    public function bulkAction(BulkPostActionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $action = $request->input('action');
            $posts = Post::whereIn('id', $request->input('post_ids', []));
            $count = (clone $posts)->count();

            match ($action) {
                'publish' => $posts->update([
                    'status' => PostStatusEnum::PUBLISHED,
                    'published_at' => DB::raw('COALESCE(published_at, NOW())'),
                ]),
                'unpublish' => $posts->update(['status' => PostStatusEnum::DRAFT, 'published_at' => null]),
                'archive' => $posts->update(['status' => PostStatusEnum::ARCHIVED]),
                default => $posts->delete(),
            };

            DB::commit();

            return $this->successResponse(['count' => $count], "{$count} post(s) {$action}d successfully.");
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->logsError(static::class, Post::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function duplicate(int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $post = Post::with('tags')->findOrFail($id);

            $duplicate = $post->replicate();
            $duplicate->title = $post->title . ' (Copy)';
            $duplicate->slug = null;
            $duplicate->status = PostStatusEnum::DRAFT;
            $duplicate->published_at = null;
            $duplicate->views_count = 0;
            $duplicate->author_id = Auth::id() ?? $post->author_id;
            $duplicate->save();

            $duplicate->tags()->sync($post->tags->pluck('id')->all());
            $duplicate->load(['author', 'category', 'tags', 'media']);
            DB::commit();

            return $this->successResponse(new PostResource($duplicate), 'Duplicated successfully.', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->logsError(static::class, Post::class, __FUNCTION__, $th, ['id' => $id]);
            return $this->someThingWentWrong($th);
        }
    }

    private function schedulePublishJob(Post $post): void
    {
        $jobId = Queue::later($post->published_at, new PublishPostJob($post, $post->published_at->toDateTimeString()));
        $post->updateQuietly(['pending_job_id' => $jobId]);
    }

    private function cancelPendingJob(Post $post): void
    {
        if ($post->pending_job_id) {
            DB::table('jobs')->where('id', $post->pending_job_id)->delete();
            $post->updateQuietly(['pending_job_id' => null]);
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => Post::count(),
                'published' => Post::published()->count(),
                'draft' => Post::draft()->count(),
                'scheduled' => Post::scheduled()->count(),
                'archived' => Post::archived()->count(),
                'total_views' => Post::sum('views_count'),
                'popular_posts' => PostResource::collection(Post::with(['author'])->popular(5)->get())->collection,
                'recent_posts' => PostResource::collection(Post::with(['author'])->latest()->limit(5)->get())->collection,
            ];

            return $this->successResponse($stats, 'Statistics fetched successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Post::class, __FUNCTION__, $th);
            return $this->someThingWentWrong($th);
        }
    }
}
