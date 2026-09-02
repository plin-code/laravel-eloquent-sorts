<?php

declare(strict_types=1);

use PlinCode\EloquentSorts\Orders\EnumOrder;
use Workbench\App\Models\Book;

/** @return array<string, int> */
function statusMap(): array
{
    return ['draft' => 1, 'sent' => 2, 'paid' => 3];
}

function makeBooks(string ...$statuses): void
{
    foreach ($statuses as $status) {
        Book::create(['status' => $status]);
    }
}

it('orders by the given map, ascending', function () {
    makeBooks('paid', 'draft', 'sent');

    $query = Book::query();
    EnumOrder::apply($query, 'status', statusMap());

    expect($query->pluck('status')->all())->toBe(['draft', 'sent', 'paid']);
})->group('integration');

it('orders by the given map, descending', function () {
    makeBooks('paid', 'draft', 'sent');

    $query = Book::query();
    EnumOrder::apply($query, 'status', statusMap(), 'desc');

    expect($query->pluck('status')->all())->toBe(['paid', 'sent', 'draft']);
})->group('integration');

it('sends values outside the map to the fallback order', function () {
    makeBooks('unknown', 'draft');

    $query = Book::query();
    EnumOrder::apply($query, 'status', statusMap());

    expect($query->pluck('status')->all())->toBe(['draft', 'unknown']);
})->group('integration');

it('honours a custom fallback order', function () {
    makeBooks('unknown', 'paid');

    $query = Book::query();
    EnumOrder::apply($query, 'status', statusMap(), 'asc', 0);

    expect($query->pluck('status')->all())->toBe(['unknown', 'paid']);
})->group('integration');

it('strips a table prefix from the column', function () {
    makeBooks('paid', 'draft');

    $query = Book::query();
    EnumOrder::apply($query, 'books.status', statusMap());

    expect($query->pluck('status')->all())->toBe(['draft', 'paid']);
})->group('integration');

it('binds values instead of interpolating them', function () {
    $query = Book::query();
    EnumOrder::apply($query, 'status', ["O'Brien" => 1, '100%' => 2]);

    expect($query->toSql())->not->toContain("O'Brien")
        ->and($query->toSql())->not->toContain('100%')
        ->and($query->getBindings())->toContain("O'Brien", '100%');
});

it('does not break on a value containing a single quote', function () {
    makeBooks("O'Brien", 'draft');

    $query = Book::query();
    EnumOrder::apply($query, 'status', ["O'Brien" => 1, 'draft' => 2]);

    expect($query->pluck('status')->all())->toBe(["O'Brien", 'draft']);
})->group('integration');

it('rejects a direction that is not asc or desc', function () {
    EnumOrder::apply(Book::query(), 'status', statusMap(), 'asc; DROP TABLE books');
})->throws(InvalidArgumentException::class);

it('accepts a direction in any casing', function () {
    makeBooks('paid', 'draft');

    $query = Book::query();
    EnumOrder::apply($query, 'status', statusMap(), 'DESC');

    expect($query->pluck('status')->all())->toBe(['paid', 'draft']);
})->group('integration');

it('wraps a column name that is a reserved word', function () {
    Book::create(['order' => 'second']);
    Book::create(['order' => 'first']);

    $query = Book::query();
    EnumOrder::apply($query, 'order', ['first' => 1, 'second' => 2]);

    expect($query->pluck('order')->all())->toBe(['first', 'second']);
})->group('integration');

it('handles a map with integer keys', function () {
    Book::create(['status' => '10']);
    Book::create(['status' => '2']);

    $query = Book::query();
    EnumOrder::apply($query, 'status', [2 => 1, 10 => 2]);

    expect($query->pluck('status')->all())->toBe(['2', '10']);
})->group('integration');

it('leaves the query untouched for an empty map', function () {
    $query = Book::query();
    $before = $query->toSql();

    EnumOrder::apply($query, 'status', []);

    expect($query->toSql())->toBe($before);
});
