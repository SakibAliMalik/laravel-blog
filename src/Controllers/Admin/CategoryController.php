<?php

namespace SakibAliMalik\Blog\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use SakibAliMalik\Blog\Filters\CategoryFilter;
use SakibAliMalik\Blog\Models\Category;
use SakibAliMalik\Blog\Requests\Category\StoreCategoryRequest;
use SakibAliMalik\Blog\Requests\Category\UpdateCategoryRequest;
use SakibAliMalik\Blog\Resources\CategoryResource;
use SakibAliMalik\Blog\Traits\ApiResponseTrait;
use SakibAliMalik\Blog\Traits\PaginationTrait;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    use ApiResponseTrait;
    use PaginationTrait;

    public function index(CategoryFilter $categoryFilter): JsonResponse
    {
        try {
            $query = Category::with(['parent'])
                ->withCount('posts')
                ->filter($categoryFilter)
                ->when(!request()->has('sort'), fn($builder) => $builder->reorder()->orderBy('order_position')->orderBy('name'));

            $tree = $this->buildTree(
                Category::with('children')->withCount('posts')->ordered()->get()
            );

            $result = $this->handlePaginationWithResource(
                $query,
                CategoryResource::class,
                [],
                ['tree' => $tree],
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
            $this->logsError(static::class, Category::class, __FUNCTION__, $th, request()->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = Category::create($request->payload());
            return $this->successResponse(
                new CategoryResource($category->load(['parent'])),
                'Created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            $this->logsError(static::class, Category::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $category = Category::with(['parent', 'children'])->withCount('posts')->findOrFail($id);
            return $this->successResponse(new CategoryResource($category), 'Record fetched successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Category::class, __FUNCTION__, $th, ['id' => $id]);
            return $this->someThingWentWrong($th);
        }
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->failedApiResponse('Category not found.', Response::HTTP_NOT_FOUND);
        }

        if ((int) $request->input('parent_id') === $id) {
            return $this->failedApiResponse(
                'Validation failed.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['parent_id' => ['Category cannot be its own parent.']]
            );
        }

        try {
            $category->update($request->payload());
            $category->load(['parent', 'children'])->loadCount('posts');

            return $this->successResponse(new CategoryResource($category), 'Updated successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Category::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);

            if ($category->posts()->exists()) {
                return $this->failedApiResponse(
                    'Cannot delete category with existing posts.',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            if ($category->hasChildren()) {
                return $this->failedApiResponse(
                    'Cannot delete category with sub-categories.',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $category->delete();
            return $this->successMessageApiResponse('Deleted successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Category::class, __FUNCTION__, $th, ['id' => $id]);
            return $this->someThingWentWrong($th);
        }
    }

    private function buildTree($categories, $parentId = null): array
    {
        $branch = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->id);
                $item = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'posts_count' => $category->posts_count,
                ];

                if (!empty($children)) {
                    $item['children'] = $children;
                }

                $branch[] = $item;
            }
        }

        return $branch;
    }
}
