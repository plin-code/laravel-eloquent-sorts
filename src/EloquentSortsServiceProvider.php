<?php

declare(strict_types=1);

namespace PlinCode\EloquentSorts;

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
}
