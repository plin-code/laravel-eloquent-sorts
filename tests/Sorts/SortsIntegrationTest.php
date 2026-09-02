<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use PlinCode\EloquentSorts\Orders\EnumOrder;
use PlinCode\EloquentSorts\Orders\RelationCountOrder;
use PlinCode\EloquentSorts\Orders\RelationOrder;
use PlinCode\EloquentSorts\Sorts\EnumSorter;
use PlinCode\EloquentSorts\Sorts\RelationCountSorter;
use PlinCode\EloquentSorts\Sorts\RelationSorter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\QueryBuilderRequest;
use Workbench\App\Models\Author;
use Workbench\App\Models\Book;

function queryFor(string $sort): QueryBuilderRequest
{
    return QueryBuilderRequest::fromRequest(Request::create("/?sort={$sort}"));
}

it('sorts by a relation column through spatie', function () {
    $calvino = Author::create(['name' => 'Calvino']);
    $buzzati = Author::create(['name' => 'Buzzati']);
    Book::create(['author_id' => $calvino->id, 'status' => 'c']);
    Book::create(['author_id' => $buzzati->id, 'status' => 'b']);

    $results = QueryBuilder::for(Book::query(), queryFor('author'))
        ->allowedSorts(AllowedSort::custom('author', new RelationSorter('authors', 'author_id')))
        ->pluck('status')
        ->all();

    expect($results)->toBe(['b', 'c']);
})->group('integration');

it('sorts by a relation column descending through spatie', function () {
    $calvino = Author::create(['name' => 'Calvino']);
    $buzzati = Author::create(['name' => 'Buzzati']);
    Book::create(['author_id' => $calvino->id, 'status' => 'c']);
    Book::create(['author_id' => $buzzati->id, 'status' => 'b']);

    $results = QueryBuilder::for(Book::query(), queryFor('-author'))
        ->allowedSorts(AllowedSort::custom('author', new RelationSorter('authors', 'author_id')))
        ->pluck('status')
        ->all();

    expect($results)->toBe(['c', 'b']);
})->group('integration');

it('sorts by a relation count through spatie', function () {
    $two = Author::create(['name' => 'two books']);
    $none = Author::create(['name' => 'no books']);
    Book::create(['author_id' => $two->id]);
    Book::create(['author_id' => $two->id]);

    $results = QueryBuilder::for(Author::query(), queryFor('-books'))
        ->allowedSorts(AllowedSort::custom('books', new RelationCountSorter('books', 'author_id')))
        ->pluck('name')
        ->all();

    expect($results)->toBe(['two books', 'no books']);
})->group('integration');

it('sorts by a custom enum order through spatie', function () {
    Book::create(['status' => 'paid']);
    Book::create(['status' => 'draft']);

    $results = QueryBuilder::for(Book::query(), queryFor('status'))
        ->allowedSorts(AllowedSort::custom('status', new EnumSorter(['draft' => 1, 'paid' => 2])))
        ->pluck('status')
        ->all();

    expect($results)->toBe(['draft', 'paid']);
})->group('integration');

it('falls back to the requested property when the enum sorter has no column', function () {
    Book::create(['status' => 'paid']);
    Book::create(['status' => 'draft']);

    $results = QueryBuilder::for(Book::query(), queryFor('status'))
        ->allowedSorts(AllowedSort::custom('status', new EnumSorter(['draft' => 1, 'paid' => 2])))
        ->pluck('status')
        ->all();

    expect($results)->toBe(['draft', 'paid']);
})->group('integration');

it('uses an explicit column over the requested property', function () {
    Book::create(['status' => 'zzz', 'order' => 'second']);
    Book::create(['status' => 'aaa', 'order' => 'first']);

    $results = QueryBuilder::for(Book::query(), queryFor('anything'))
        ->allowedSorts(AllowedSort::custom(
            'anything',
            new EnumSorter(['first' => 1, 'second' => 2], 'order'),
        ))
        ->pluck('status')
        ->all();

    expect($results)->toBe(['aaa', 'zzz']);
})->group('integration');

it('honours the soft delete column through the sorter', function () {
    $query = Book::query();
    (new RelationSorter('authors', 'author_id', 'name', 'id', 'deleted_at'))($query, false, 'author');

    expect($query->toSql())->toContain('deleted_at');
});

it('produces the exact same sql and bindings as RelationOrder::apply()', function () {
    $viaSorter = Book::query();
    (new RelationSorter('authors', 'author_id', 'name', 'id', 'deleted_at'))($viaSorter, true, 'author');

    $viaOrder = Book::query();
    RelationOrder::apply($viaOrder, 'authors', 'author_id', 'name', 'desc', 'id', 'deleted_at');

    expect($viaSorter->toSql())->toBe($viaOrder->toSql())
        ->and($viaSorter->getBindings())->toBe($viaOrder->getBindings());
});

it('produces the exact same sql and bindings as RelationCountOrder::apply()', function () {
    $viaSorter = Author::query();
    (new RelationCountSorter('books', 'author_id', 'id', 'deleted_at'))($viaSorter, true, 'books');

    $viaOrder = Author::query();
    RelationCountOrder::apply($viaOrder, 'books', 'author_id', 'desc', 'id', 'deleted_at');

    expect($viaSorter->toSql())->toBe($viaOrder->toSql())
        ->and($viaSorter->getBindings())->toBe($viaOrder->getBindings());
});

it('produces the exact same sql and bindings as EnumOrder::apply()', function () {
    $viaSorter = Book::query();
    (new EnumSorter(['draft' => 1, 'paid' => 2], null, 7))($viaSorter, true, 'status');

    $viaOrder = Book::query();
    EnumOrder::apply($viaOrder, 'status', ['draft' => 1, 'paid' => 2], 'desc', 7);

    expect($viaSorter->toSql())->toBe($viaOrder->toSql())
        ->and($viaSorter->getBindings())->toBe($viaOrder->getBindings());
});

it('uses the explicit column instead of the property for EnumOrder equivalence', function () {
    $viaSorter = Book::query();
    (new EnumSorter(['first' => 1, 'second' => 2], 'order'))($viaSorter, false, 'anything');

    $viaOrder = Book::query();
    EnumOrder::apply($viaOrder, 'order', ['first' => 1, 'second' => 2], 'asc');

    expect($viaSorter->toSql())->toBe($viaOrder->toSql())
        ->and($viaSorter->getBindings())->toBe($viaOrder->getBindings());
});
