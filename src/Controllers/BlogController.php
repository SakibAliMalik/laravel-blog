<?php

namespace SakibAliMalik\Blog\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use SakibAliMalik\Blog\Filters\PostFilter;
use SakibAliMalik\Blog\Filters\TagFilter;
use SakibAliMalik\Blog\Models\Category;
use SakibAliMalik\Blog\Models\Post;
use SakibAliMalik\Blog\Models\PostView;
use SakibAliMalik\Blog\Models\Tag;
use SakibAliMalik\Blog\Resources\PostResource;
use SakibAliMalik\Blog\Resources\TagResource;
use SakibAliMalik\Blog\Traits\ApiResponseTrait;
use SakibAliMalik\Blog\Traits\PaginationTrait;
use Symfony\Component\HttpFoundation\Response;

class BlogController extends Controller
{
    use ApiResponseTrait;
    use PaginationTrait;

    public function categories(): JsonResponse
    {
        try {
            $query = Category::select('id', 'name', 'slug');
            $result = $this->handlePagination($query, [], [], request()->boolean('pagination.export', false));

            if (($result['success'] ?? true) === false) {
                return $this->failedApiResponse(
                    $result['message'] ?? 'Something went wrong.',
                    $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                    $result['errors'] ?? []
                );
            }

            return $this->successResponse($result, 'Records retrieved successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Category::class, __FUNCTION__, $th, request()->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function blogs(PostFilter $postFilter): JsonResponse
    {
        try {
            $query = Post::query()
                ->filter($postFilter)
                ->published()
                ->select('id', 'category_id', 'title', 'slug', 'featured_image', 'status', 'read_time', 'published_at')
                ->with('category:id,name');

            $result = $this->handlePagination($query, [], [], request()->boolean('pagination.export', false));

            if (($result['success'] ?? true) === false) {
                return $this->failedApiResponse(
                    $result['message'] ?? 'Something went wrong.',
                    $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                    $result['errors'] ?? []
                );
            }

            $result['categories'] = Category::select('id', 'name', 'slug', 'description', 'icon')
                ->withCount(['posts' => fn($q) => $q->published()])
                ->orderBy('posts_count', 'asc')
                ->get()
                ->map(fn($c) => [
                    'id'          => $c->id,
                    'name'        => $c->name,
                    'slug'        => $c->slug,
                    'description' => $c->description,
                    'icon'        => $c->icon,
                    'blogs_count' => $c->posts_count,
                ])
                ->values();

            return $this->successResponse($result, 'Records retrieved successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Post::class, __FUNCTION__, $th, request()->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function recentBlogs(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->failedApiResponse('Validation failed.', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        try {
            $posts = Post::published()
                ->reorder()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->select('id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'views_count', 'read_time')
                ->limit((int) $request->integer('limit', 6))
                ->get();

            return $this->successResponse($posts, 'Recent posts retrieved.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Post::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function tags(TagFilter $tagFilter): JsonResponse
    {
        try {
            $query = Tag::whereHas('posts', fn($q) => $q->published())
                ->withCount(['posts' => fn($q) => $q->published()])
                ->filter($tagFilter)
                ->when(!request()->has('sort'), fn($builder) => $builder->reorder()->orderBy('name'));

            $result = $this->handlePaginationWithResource(
                $query,
                TagResource::class,
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

            return $this->successResponse($result, 'Tags retrieved successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Tag::class, __FUNCTION__, $th, request()->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function blogShow(string $slug): JsonResponse
    {
        try {
            $post = Post::with(['author', 'category', 'tags', 'media'])->where('slug', $slug)->first();

            if (!$post) {
                return $this->failedApiResponse('Blog post not found.', Response::HTTP_NOT_FOUND);
            }

            $data = (new PostResource($post))->toArray(request());
            $data['helpful_articles'] = Post::where('category_id', $post->category_id)
                ->where('id', '!=', $post->id)
                ->select('id', 'title', 'slug', 'featured_image', 'status', 'published_at')
                ->latest('published_at')
                ->take(3)
                ->get();
            $data['other_categories'] = Category::where('id', '!=', $post->category_id)
                ->select('id', 'name', 'slug')
                ->withCount('posts')
                ->orderBy('posts_count', 'desc')
                ->take(3)
                ->get();
            $data['trending_resource'] = Post::where('category_id', $post->category_id)
                ->where('id', '!=', $post->id)
                ->select('id', 'title', 'slug', 'featured_image', 'status', 'published_at', 'views_count')
                ->latest('views_count')
                ->take(6)
                ->get();

            return $this->successResponse($data, 'Post fetched successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Post::class, __FUNCTION__, $th, ['slug' => $slug]);
            return $this->someThingWentWrong($th);
        }
    }

    public function storeView(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
            'visitor_id' => 'required|string|max:64',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->failedApiResponse('Validation failed.', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        try {
            $postView = PostView::firstOrCreate(
                ['post_id' => (int) $request->post_id, 'visitor_id' => $request->visitor_id],
                [
                    'ip_address' => $request->ip_address ?? $request->ip(),
                    'user_agent' => $request->user_agent ?? $request->userAgent(),
                ]
            );

            if ($postView->wasRecentlyCreated) {
                Post::where('id', $request->post_id)->increment('views_count');
            }

            return $this->successResponse(['post_id' => $request->post_id], 'View recorded.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, PostView::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }
}
