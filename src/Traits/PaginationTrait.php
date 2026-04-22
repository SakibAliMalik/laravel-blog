<?php

namespace SakibAliMalik\Blog\Traits;

use Illuminate\Database\Eloquent\Builder;

trait PaginationTrait
{
    use ApiResponseTrait;

    protected function handlePagination(
        Builder $query,
        array $metaData = [],
        array $responseData = [],
        bool $export = false
    ): array {
        $validationResult = $this->validateAndExtractPagination(request());

        if (!$validationResult['success']) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'status_code' => $validationResult['status_code'],
                'errors' => $validationResult['errors'],
            ];
        }

        if ($export) {
            return array_merge(['records' => $query->get()], $responseData);
        }

        $records = $query->paginate($validationResult['per_page'], ['*'], 'page', $validationResult['page']);
        $data = $this->formatPaginatedResponse($records, $metaData);

        return array_merge($data, $responseData);
    }

    protected function handlePaginationWithResource(
        Builder $query,
        string $resourceClass,
        array $metaData = [],
        array $responseData = [],
        bool $export = false
    ): array {
        $validationResult = $this->validateAndExtractPagination(request());

        if (!$validationResult['success']) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'status_code' => $validationResult['status_code'],
                'errors' => $validationResult['errors'],
            ];
        }

        if ($export) {
            return array_merge(
                ['records' => $resourceClass::collection($query->get())],
                $responseData
            );
        }

        $records = $query->paginate($validationResult['per_page'], ['*'], 'page', $validationResult['page']);
        $data = $this->formatPaginatedResponse($records, $metaData);
        $data['data'] = $resourceClass::collection($records);

        return array_merge($data, $responseData);
    }
}
