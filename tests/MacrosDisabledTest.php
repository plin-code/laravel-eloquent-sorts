<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use PlinCode\EloquentSorts\EloquentSortsServiceProvider;
use PlinCode\EloquentSorts\Orders\RelationOrder;
use PlinCode\EloquentSorts\Sorts\RelationSorter;
use Workbench\App\Models\Book;

beforeEach(function () {
    // Le macro vivono in una proprieta' statica di Builder e sopravvivono al
    // boot successivo. Eloquent\Builder non ha flushMacros() (non usa il trait
    // Macroable), quindi l'unico modo di azzerarle e' la reflection.
    $macros = new ReflectionProperty(Builder::class, 'macros');
    $macros->setValue(null, []);
});

it('registers no macro when the config says so', function () {
    config()->set('eloquent-sorts.register_macros', false);

    $this->app->register(EloquentSortsServiceProvider::class, true);

    expect(Builder::hasGlobalMacro('orderByRelation'))->toBeFalse()
        ->and(Builder::hasGlobalMacro('orderByCount'))->toBeFalse()
        ->and(Builder::hasGlobalMacro('orderByEnum'))->toBeFalse();
});

it('keeps the order classes working with macros disabled', function () {
    config()->set('eloquent-sorts.register_macros', false);

    $query = Book::query();
    RelationOrder::apply($query, 'authors', 'author_id');

    expect($query->toSql())->toContain('order by');
});

it('keeps the sort classes working with macros disabled', function () {
    config()->set('eloquent-sorts.register_macros', false);

    $query = Book::query();
    (new RelationSorter('authors', 'author_id'))($query, false, 'author');

    expect($query->toSql())->toContain('order by');
});
