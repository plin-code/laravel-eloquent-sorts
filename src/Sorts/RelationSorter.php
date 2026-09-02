<?php

declare(strict_types=1);

namespace PlinCode\EloquentSorts\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PlinCode\EloquentSorts\Orders\RelationOrder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * @implements Sort<Model>
 */
final readonly class RelationSorter implements Sort
{
    public function __construct(
        private string $relationTable,
        private string $foreignKey,
        private string $sortColumn = 'name',
        private string $ownerKey = 'id',
        private ?string $softDeleteColumn = null,
    ) {}

    /**
     * @param  Builder<Model>  $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        RelationOrder::apply(
            $query,
            $this->relationTable,
            $this->foreignKey,
            $this->sortColumn,
            $descending ? 'desc' : 'asc',
            $this->ownerKey,
            $this->softDeleteColumn,
        );
    }
}
