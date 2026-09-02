<?php

declare(strict_types=1);

namespace PlinCode\EloquentSorts\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PlinCode\EloquentSorts\EloquentSortsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            EloquentSortsServiceProvider::class,
        ];
    }
}
