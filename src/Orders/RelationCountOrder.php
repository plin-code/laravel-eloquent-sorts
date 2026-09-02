<?php

declare(strict_types=1);

namespace PlinCode\EloquentSorts\Orders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PlinCode\EloquentSorts\Support\Direction;

final class RelationCountOrder
{
    /**
     * Order rows by how many related rows point at them, through a
     * correlated COUNT subquery. Rows with no related records count as
     * zero, not null.
     *
     * Identifiers are passed to the query builder rather than interpolated
     * into raw SQL, so the grammar quotes them, reserved words included.
     *
     * @param  Builder<Model>  $query
     * @param  string  $relatedTable  table holding the rows to count
     * @param  string  $foreignKey  column of $relatedTable pointing back
     * @param  string  $direction  asc or desc
     * @param  string  $primaryKey  key of the queried table it points at
     * @param  string|null  $softDeleteColumn  when given, related rows with a
     *                                         non null value here are not counted
     * @return Builder<Model>
     *
     * @throws InvalidArgumentException when $direction is not asc or desc
     */
    public static function apply(
        Builder $query,
        string $relatedTable,
        string $foreignKey,
        string $direction = 'asc',
        string $primaryKey = 'id',
        ?string $softDeleteColumn = null,
    ): Builder {
        $direction = Direction::normalise($direction);

        $model = $query->getModel();

        $subquery = DB::connection($model->getConnectionName())
            ->table($relatedTable)
            ->selectRaw('COUNT(*)')
            ->whereColumn(
                "{$relatedTable}.{$foreignKey}",
                "{$model->getTable()}.{$primaryKey}",
            );

        if ($softDeleteColumn !== null) {
            $subquery->whereNull("{$relatedTable}.{$softDeleteColumn}");
        }

        return $query->orderBy($subquery, $direction);
    }
}
