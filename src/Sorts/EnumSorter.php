<?php

declare(strict_types=1);

namespace PlinCode\EloquentSorts\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PlinCode\EloquentSorts\Orders\EnumOrder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * @implements Sort<Model>
 */
final readonly class EnumSorter implements Sort
{
    /**
     * @param  array<string|int, int>  $casesMap  [value => sort position]
     * @param  string|null  $column  falls back to the requested property
     */
    public function __construct(
        private array $casesMap,
        private ?string $column = null,
        private int $fallbackOrder = 99,
    ) {}

    /**
     * @param  Builder<Model>  $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        EnumOrder::apply(
            $query,
            $this->column ?? $property,
            $this->casesMap,
            $descending ? 'desc' : 'asc',
            $this->fallbackOrder,
        );
    }
}
