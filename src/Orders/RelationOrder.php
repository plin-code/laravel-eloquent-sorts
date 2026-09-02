<?php

declare(strict_types=1);

namespace PlinCode\EloquentSorts\Orders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PlinCode\EloquentSorts\Support\Direction;

final class RelationOrder
{
    private function __construct() {}

    /**
     * Order rows by a column of a related table, through a correlated
     * subquery. No join, so the result set is not multiplied.
     *
     * Identifiers are passed to the query builder rather than interpolated
     * into raw SQL, so the grammar quotes them, reserved words included.
     *
     * @param  Builder<Model>  $query
     * @param  string  $relatedTable  table holding the value to sort on
     * @param  string  $foreignKey  column on the queried table pointing at it
     * @param  string  $sortColumn  column of $relatedTable to sort by
     * @param  string  $direction  asc or desc
     * @param  string  $ownerKey  key of $relatedTable the foreign key points at
     * @param  string|null  $softDeleteColumn  when given, related rows with a
     *                                         non null value here are ignored
     * @return Builder<Model>
     *
     * @throws InvalidArgumentException when $direction is not asc or desc
     */
    public static function apply(
        Builder $query,
        string $relatedTable,
        string $foreignKey,
        string $sortColumn = 'name',
        string $direction = 'asc',
        string $ownerKey = 'id',
        ?string $softDeleteColumn = null,
    ): Builder {
        $direction = Direction::normalise($direction);

        $model = $query->getModel();

        $subquery = DB::connection($model->getConnectionName())
            ->table($relatedTable)
            ->select($sortColumn)
            ->whereColumn(
                "{$relatedTable}.{$ownerKey}",
                "{$model->getTable()}.{$foreignKey}",
            )
            ->limit(1);

        if ($softDeleteColumn !== null) {
            $subquery->whereNull("{$relatedTable}.{$softDeleteColumn}");
        }

        return $query->orderBy($subquery, $direction);
    }
}
