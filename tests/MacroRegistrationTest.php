<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use PlinCode\EloquentSorts\Orders\EnumOrder;
use PlinCode\EloquentSorts\Orders\RelationCountOrder;
use PlinCode\EloquentSorts\Orders\RelationOrder;
use Workbench\App\Models\Author;
use Workbench\App\Models\Book;

it('registers the three macros by default', function () {
    // hasMacro() su Eloquent\Builder e' un metodo di istanza: il controllo
    // statico e' hasGlobalMacro(). L'Eloquent Builder non usa il trait
    // Macroable, si gestisce le macro da solo via __callStatic.
    expect(Builder::hasGlobalMacro('orderByRelation'))->toBeTrue()
        ->and(Builder::hasGlobalMacro('orderByCount'))->toBeTrue()
        ->and(Builder::hasGlobalMacro('orderByEnum'))->toBeTrue();
});

it('produces the same sql as the order class, for orderByRelation', function () {
    $viaMacro = Book::query()->orderByRelation('authors', 'author_id');

    $viaClass = Book::query();
    RelationOrder::apply($viaClass, 'authors', 'author_id');

    expect($viaMacro->toSql())->toBe($viaClass->toSql());
});

it('produces the same sql as the order class, for orderByCount', function () {
    $viaMacro = Author::query()->orderByCount('books', 'author_id');

    $viaClass = Author::query();
    RelationCountOrder::apply($viaClass, 'books', 'author_id');

    expect($viaMacro->toSql())->toBe($viaClass->toSql());
});

it('produces the same sql as the order class, for orderByEnum', function () {
    $map = ['draft' => 1, 'paid' => 2];

    $viaMacro = Book::query()->orderByEnum('status', $map);

    $viaClass = Book::query();
    EnumOrder::apply($viaClass, 'status', $map);

    expect($viaMacro->toSql())->toBe($viaClass->toSql())
        ->and($viaMacro->getBindings())->toBe($viaClass->getBindings());
});

it('passes the soft delete column through the macro', function () {
    $query = Book::query()->orderByRelation('authors', 'author_id', 'name', 'asc', 'id', 'deleted_at');

    expect($query->toSql())->toContain('deleted_at');
});
