<?php

declare(strict_types=1);

arch('the orders do not depend on spatie')
    ->expect('PlinCode\EloquentSorts\Orders')
    ->not->toUse('Spatie\QueryBuilder')
    ->not->toUse('Spatie\LaravelPackageTools');

arch('the support classes do not depend on spatie')
    ->expect('PlinCode\EloquentSorts\Support')
    ->not->toUse('Spatie\QueryBuilder')
    ->not->toUse('Spatie\LaravelPackageTools');

arch('the package does not depend on the host application')
    ->expect('PlinCode\EloquentSorts')
    ->not->toUse('App');

arch('every class is final')
    ->expect('PlinCode\EloquentSorts')
    ->toBeFinal();

arch('nothing is left behind')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('strict types everywhere')
    ->expect('PlinCode\EloquentSorts')
    ->toUseStrictTypes();
