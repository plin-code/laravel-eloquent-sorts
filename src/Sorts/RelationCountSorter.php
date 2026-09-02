<?php

declare(strict_types=1);

namespace PlinCode\EloquentSorts\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PlinCode\EloquentSorts\Orders\RelationCountOrder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * @implements Sort<Model>
 */
final readonly class RelationCountSorter implements Sort
{
    public function __construct(
        private string $relatedTable,
        private string $foreignKey,
        private string $primaryKey = 'id',
        private ?string $softDeleteColumn = null,
    ) {}

    /**
     * @param  Builder<Model>  $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        RelationCountOrder::apply(
            $query,
            $this->relatedTable,
            $this->foreignKey,
            $descending ? 'desc' : 'asc',
            $this->primaryKey,
            $this->softDeleteColumn,
        );
    }
}
