<?php

declare(strict_types=1);

namespace PlinCode\EloquentSorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PlinCode\EloquentSorts\Orders\EnumOrder;
use PlinCode\EloquentSorts\Orders\RelationCountOrder;
use PlinCode\EloquentSorts\Orders\RelationOrder;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class EloquentSortsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-eloquent-sorts')
            ->hasConfigFile('eloquent-sorts');
    }

    public function packageBooted(): void
    {
        if (! config('eloquent-sorts.register_macros', true)) {
            return;
        }

        Builder::macro('orderByRelation', function (
            string $relatedTable,
            string $foreignKey,
            string $sortColumn = 'name',
            string $direction = 'asc',
            string $ownerKey = 'id',
            ?string $softDeleteColumn = null,
        ): Builder {
            /** @var Builder<Model> $this */
            return RelationOrder::apply(
                $this, $relatedTable, $foreignKey, $sortColumn, $direction, $ownerKey, $softDeleteColumn,
            );
        });

        Builder::macro('orderByRelationCount', function (
            string $relatedTable,
            string $foreignKey,
            string $direction = 'asc',
            string $localKey = 'id',
            ?string $softDeleteColumn = null,
        ): Builder {
            /** @var Builder<Model> $this */
            return RelationCountOrder::apply(
                $this, $relatedTable, $foreignKey, $direction, $localKey, $softDeleteColumn,
            );
        });

        Builder::macro('orderByEnum', function (
            string $column,
            array $casesMap,
            string $direction = 'asc',
            int $fallbackOrder = 99,
        ): Builder {
            /** @var Builder<Model> $this */
            return EnumOrder::apply($this, $column, $casesMap, $direction, $fallbackOrder);
        });
    }
}
