<?php

namespace SakibAliMalik\Blog\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use SakibAliMalik\Blog\Filters\TagFilter;
use SakibAliMalik\Blog\Models\Tag;
use SakibAliMalik\Blog\Requests\Tag\StoreTagRequest;
use SakibAliMalik\Blog\Requests\Tag\UpdateTagRequest;
use SakibAliMalik\Blog\Resources\TagResource;
use SakibAliMalik\Blog\Traits\ApiResponseTrait;
use SakibAliMalik\Blog\Traits\PaginationTrait;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller
{
    use ApiResponseTrait;
    use PaginationTrait;

    public function index(TagFilter $tagFilter): JsonResponse
    {
        try {
            $query = Tag::withCount('posts')
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

            return $this->successResponse($result, 'Records retrieved successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Tag::class, __FUNCTION__, $th, request()->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        try {
            $tag = Tag::create($request->payload());
            return $this->successResponse(
                new TagResource($tag->loadCount('posts')),
                'Created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            $this->logsError(static::class, Tag::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $tag = Tag::withCount('posts')->findOrFail($id);
            return $this->successResponse(new TagResource($tag), 'Record fetched successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Tag::class, __FUNCTION__, $th, ['id' => $id]);
            return $this->someThingWentWrong($th);
        }
    }

    public function update(UpdateTagRequest $request, int $id): JsonResponse
    {
        $tag = Tag::find($id);

        if (!$tag) {
            return $this->failedApiResponse('Tag not found.', Response::HTTP_NOT_FOUND);
        }

        try {
            $tag->update($request->payload());
            $tag->loadCount('posts');
            return $this->successResponse(new TagResource($tag), 'Updated successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Tag::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $tag = Tag::findOrFail($id);
            $postsCount = $tag->posts()->count();

            if ($postsCount > 0 && !request()->boolean('force', false)) {
                return $this->failedApiResponse(
                    "Cannot delete tag used in {$postsCount} post(s). Use ?force=true to force delete.",
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $tag->delete();
            return $this->successMessageApiResponse('Deleted successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Tag::class, __FUNCTION__, $th, ['id' => $id]);
            return $this->someThingWentWrong($th);
        }
    }

    public function popular(): JsonResponse
    {
        try {
            $tags = Tag::popular(20)->get();
            return $this->successResponse(TagResource::collection($tags)->collection, 'Popular tags retrieved.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Tag::class, __FUNCTION__, $th);
            return $this->someThingWentWrong($th);
        }
    }
}
