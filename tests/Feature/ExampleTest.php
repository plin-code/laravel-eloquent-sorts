<?php

declare(strict_types=1);

use PlinCode\EloquentSorts\EloquentSorts;

it('resolves the singleton', function () {
    expect(app(EloquentSorts::class))->toBeInstanceOf(EloquentSorts::class);
});

it('returns the same instance from the container', function () {
    expect(app(EloquentSorts::class))->toBe(app(EloquentSorts::class));
});

it('merges the package config', function () {
    expect(config('laravel-eloquent-sorts.placeholder'))->toBe('default');
});
