<?php

declare(strict_types=1);

namespace PlinCode\EloquentSorts\Orders;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use PlinCode\EloquentSorts\Support\Direction;

final class EnumOrder
{
    /**
     * Order rows by an arbitrary map of column value to sort position,
     * built as a CASE expression.
     *
     * Values are bound, never interpolated: the map often comes from an enum
     * in the consuming application, but a package cannot assume that for
     * every future caller.
     *
     * Sort positions and the fallback are cast to int and inlined instead of
     * bound, because PostgreSQL cannot infer the type of a parameter sitting
     * in the THEN branch of a CASE inside ORDER BY.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string|int, int>  $casesMap  [value => sort position]
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     *
     * @throws InvalidArgumentException when $direction is not asc or desc
     */
    public static function apply(
        Builder $query,
        string $column,
        array $casesMap,
        string $direction = 'asc',
        int $fallbackOrder = 99,
    ): Builder {
        $direction = Direction::normalise($direction);

        if ($casesMap === []) {
            return $query;
        }

        if (str_contains($column, '.')) {
            $parts = explode('.', $column);
            $column = (string) end($parts);
        }

        $wrapped = $query->getGrammar()->wrap(
            $query->getModel()->getTable().'.'.$column,
        );

        $cases = '';
        $bindings = [];

        foreach ($casesMap as $value => $order) {
            $cases .= ' WHEN ? THEN '.(int) $order;
            $bindings[] = (string) $value;
        }

        return $query->orderByRaw(
            "CASE {$wrapped}{$cases} ELSE ".(int) $fallbackOrder." END {$direction}",
            $bindings,
        );
    }
}
