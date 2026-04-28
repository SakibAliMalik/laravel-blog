<?php

namespace SakibAliMalik\Blog\Traits;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponseTrait
{
    public function successResponse($data, $message, $code = 200)
    {
        return response()->json([
            'message' => $message,
            'status' => $code,
            'data' => $data,
        ], $code);
    }

    public function successMessageApiResponse($message, $code = 200)
    {
        return response()->json([
            'status' => $code,
            'message' => $message,
        ], $code);
    }

    public function failedApiResponse($message, $code, $error = '')
    {
        $body = ['message' => $message, 'status' => $code];

        if (!empty($error)) {
            $body['errors'] = $error;
        }

        return response()->json($body, $code);
    }

    public function someThingWentWrong($error)
    {
        $code = $error->getCode();

        if ($code === 400) {
            return response()->json([
                'message' => $error->getMessage(),
                'status' => $code,
            ], $code);
        }

        return $this->failedApiResponse(
            trans('messages.something_went_wrong', [], null) ?? 'Something went wrong.',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function logsError($controller, $model, $functionName, $error, $payload = null): void
    {
        Log::error('[Blog] ' . $functionName, [
            'controller' => $controller,
            'model' => $model,
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'payload' => $payload,
        ]);
    }

    public function validateAndExtractPagination(Request $request, array $rules = []): array
    {
        $validator = Validator::make($request->all(), array_merge([
            'pagination' => 'nullable|array',
            'pagination.page' => 'nullable|integer|min:1',
            'pagination.per_page' => 'nullable|integer|min:1',
        ], $rules));

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->messages()->toArray(),
                'status_code' => Response::HTTP_BAD_REQUEST,
            ];
        }

        $pagination = $request->input('pagination');

        return [
            'success' => true,
            'page' => $pagination['page'] ?? 1,
            'per_page' => $pagination['per_page'] ?? 20,
            'export' => $pagination['export'] ?? false,
        ];
    }

    public function formatPaginatedResponse(LengthAwarePaginator $paginatedData, array $additionalData = []): array
    {
        return [
            'meta' => array_merge([
                'page' => $paginatedData->currentPage(),
                'pages' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
            ], $additionalData),
            'data' => $paginatedData->items(),
        ];
    }
}
