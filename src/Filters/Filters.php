<?php

namespace SakibAliMalik\Blog\Filters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

abstract class Filters
{
    protected $request;
    protected $builder;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply($builder)
    {
        $this->builder = $builder;
        $this->validateFilters();

        foreach ($this->getFilters() as $filter => $value) {
            if (isset($value) && method_exists($this, $filter)) {
                $this->$filter($value);
            }
        }

        return $this->builder;
    }

    public function getFilters(): array
    {
        $collection = collect($this->request->all())->get('query', []);

        $sort = $this->request->sort;
        if (!empty($sort)) {
            $table = $this->builder->getModel()->getTable();
            $sortBy = $sort['sort_by'] ?? 'id';
            $sortOrder = $sort['sort_order'] ?? 'asc';

            if (Schema::hasColumn($table, $sortBy)) {
                $this->builder->reorder()->orderBy($sortBy, $sortOrder);
            } else {
                throw new \InvalidArgumentException("Invalid sort column: {$sortBy}", Response::HTTP_BAD_REQUEST);
            }
        } else {
            $this->builder->orderBy('id', 'desc');
        }

        $searchByFields = $this->request->searchByFields;
        if (!empty($searchByFields['fields'])) {
            foreach ($searchByFields['fields'] as $key => $value) {
                if (!empty($value)) {
                    $this->builder->whereIn($key, $value);
                }
            }
        }

        return is_array($collection) ? $collection : [];
    }

    protected function validateFilters(): void
    {
        $rules = $this->rules();
        if (empty($rules)) {
            return;
        }

        $mapped = collect($rules)
            ->mapWithKeys(fn($rule, $key) => ["query.$key" => $rule])
            ->toArray();

        $validator = Validator::make($this->request->all(), $mapped);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }
    }

    protected function rules(): array
    {
        return [];
    }

    protected function normalizeString(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value));
    }
}
