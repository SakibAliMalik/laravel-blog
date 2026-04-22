<?php

namespace SakibAliMalik\Blog\Traits;

use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

trait CrudTrait
{
    public function scopeFilter($query, $filters)
    {
        $filters->apply($query);
    }

    public function scopeSort($query, ?array $sort = null)
    {
        $sortBy = $sort['sort_by'] ?? 'id';
        $sortOrder = $sort['sort_order'] ?? 'asc';
        $table = $query->getModel()->getTable();

        if (Schema::hasColumn($table, $sortBy)) {
            return $query->orderBy($sortBy, $sortOrder);
        }

        throw new \InvalidArgumentException("Invalid sort column: {$sortBy}", Response::HTTP_BAD_REQUEST);
    }
}
