<?php

declare(strict_types=1);

use PlinCode\EloquentSorts\Orders\RelationCountOrder;
use Workbench\App\Models\Author;
use Workbench\App\Models\Book;

function seedAuthorsWithBookCounts(): void
{
    $two = Author::create(['name' => 'two books']);
    $none = Author::create(['name' => 'no books']);
    $one = Author::create(['name' => 'one book']);

    Book::create(['author_id' => $two->id]);
    Book::create(['author_id' => $two->id]);
    Book::create(['author_id' => $one->id]);
}

it('orders by the count of related rows, ascending', function () {
    seedAuthorsWithBookCounts();

    $query = Author::query();
    RelationCountOrder::apply($query, 'books', 'author_id');

    expect($query->pluck('name')->all())->toBe(['no books', 'one book', 'two books']);
})->group('integration');

it('orders by the count of related rows, descending', function () {
    seedAuthorsWithBookCounts();

    $query = Author::query();
    RelationCountOrder::apply($query, 'books', 'author_id', 'desc');

    expect($query->pluck('name')->all())->toBe(['two books', 'one book', 'no books']);
})->group('integration');

it('counts zero rather than null for a row with no related records', function () {
    Author::create(['name' => 'no books']);

    $query = Author::query();
    RelationCountOrder::apply($query, 'books', 'author_id');

    expect($query->pluck('name')->all())->toBe(['no books']);
})->group('integration');

it('uses a custom primary key on the queried table', function () {
    $a = Author::create(['name' => 'a']);
    Book::create(['author_id' => $a->id]);

    $query = Author::query();
    RelationCountOrder::apply($query, 'books', 'author_id', 'asc', 'id');

    expect($query->pluck('name')->all())->toBe(['a']);
})->group('integration');

it('ignores soft deleted related rows when a column is given', function () {
    $one = Author::create(['name' => 'one live book']);
    $none = Author::create(['name' => 'only deleted books']);

    Book::create(['author_id' => $one->id]);
    Book::create(['author_id' => $none->id, 'deleted_at' => now()]);

    $query = Author::query();
    RelationCountOrder::apply($query, 'books', 'author_id', 'desc', 'id', 'deleted_at');

    expect($query->pluck('name')->all())->toBe(['one live book', 'only deleted books']);
})->group('integration');

it('applies no soft delete filter by default', function () {
    $query = Author::query();
    RelationCountOrder::apply($query, 'books', 'author_id');

    expect($query->toSql())->not->toContain('deleted_at');
});

it('adds the soft delete filter only when asked', function () {
    $query = Author::query();
    RelationCountOrder::apply($query, 'books', 'author_id', 'asc', 'id', 'deleted_at');

    expect($query->toSql())->toContain('deleted_at');
});

it('rejects a direction that is not asc or desc', function () {
    RelationCountOrder::apply(Author::query(), 'books', 'author_id', 'upwards');
})->throws(InvalidArgumentException::class);
