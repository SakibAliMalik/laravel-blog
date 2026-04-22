<?php

namespace SakibAliMalik\Blog\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use SakibAliMalik\Blog\Filters\MediaFilter;
use SakibAliMalik\Blog\Models\Media;
use SakibAliMalik\Blog\Requests\Media\UpdateMediaRequest;
use SakibAliMalik\Blog\Requests\Media\UploadMediaRequest;
use SakibAliMalik\Blog\Resources\MediaResource;
use SakibAliMalik\Blog\Traits\ApiResponseTrait;
use SakibAliMalik\Blog\Traits\FileUploadTrait;
use SakibAliMalik\Blog\Traits\PaginationTrait;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    use ApiResponseTrait;
    use FileUploadTrait;
    use PaginationTrait;

    public function index(MediaFilter $mediaFilter): JsonResponse
    {
        try {
            $query = Media::with('uploader')
                ->filter($mediaFilter)
                ->when(!request()->has('sort'), fn($builder) => $builder->reorder()->orderByDesc('created_at'));

            $result = $this->handlePaginationWithResource(
                $query,
                MediaResource::class,
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
            $this->logsError(static::class, Media::class, __FUNCTION__, $th, request()->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function upload(UploadMediaRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $mimeType = $file->getMimeType();
            $fileType = $this->detectFileType($mimeType);
            $path = $this->fileUploader('media/' . $fileType, $file);

            $width = $height = null;
            if ($fileType === 'image') {
                try {
                    [$width, $height] = getimagesize($file->getRealPath());
                } catch (\Throwable) {
                }
            }

            $media = Media::create(array_merge([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $fileType,
                'mime_type' => $mimeType,
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'uploaded_by' => $request->user()->id,
            ], $request->meta()));

            DB::commit();

            return $this->successResponse(
                new MediaResource($media->load('uploader')),
                'File uploaded successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->logsError(static::class, Media::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function update(UpdateMediaRequest $request, int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return $this->failedApiResponse('Media not found.', Response::HTTP_NOT_FOUND);
        }

        try {
            $media->update($request->payload());
            $media->load('uploader');
            return $this->successResponse(new MediaResource($media), 'Updated successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Media::class, __FUNCTION__, $th, $request->all());
            return $this->someThingWentWrong($th);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $media = Media::find($id);

            if (!$media) {
                return $this->failedApiResponse('Media not found.', Response::HTTP_NOT_FOUND);
            }

            $media->deleteFile();
            $media->delete();

            return $this->successMessageApiResponse('Deleted successfully.');
        } catch (\Throwable $th) {
            $this->logsError(static::class, Media::class, __FUNCTION__, $th, ['id' => $id]);
            return $this->someThingWentWrong($th);
        }
    }

    private function detectFileType(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            default => 'document',
        };
    }
}
