<p align="center">
  <img src="https://raw.githubusercontent.com/plin-code/laravel-eloquent-sorts/main/art/banner.png" alt="Laravel Eloquent Sorts">
</p>

<div align="center">
    <h1>Laravel Eloquent Sorts</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/plin-code/laravel-eloquent-sorts"><img src="https://img.shields.io/packagist/v/plin-code/laravel-eloquent-sorts.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/plin-code/laravel-eloquent-sorts"><img src="https://img.shields.io/packagist/php-v/plin-code/laravel-eloquent-sorts.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/plin-code/laravel-eloquent-sorts"><img src="https://badge.laravel.cloud/badge/plin-code/laravel-eloquent-sorts?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/plin-code/laravel-eloquent-sorts/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/plin-code/laravel-eloquent-sorts/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/plin-code/laravel-eloquent-sorts"><img src="https://img.shields.io/packagist/dt/plin-code/laravel-eloquent-sorts.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Reusable Eloquent sorts for Laravel: order by a relation column, by relation count, or by a custom enum order, with adapters for spatie/laravel-query-builder.

## What it solves

`spatie/laravel-query-builder` lets an API request choose how a query is sorted, but the core package only ships three sort implementations: a plain field, a callback and a scope. Three things people keep asking for are not among them.

Ordering by a column on a related table (through a correlated subquery, no join) was requested in [issue #36](https://github.com/spatie/laravel-query-builder/issues/36), opened in February 2018 and closed six months later without a merge. The maintainer's reply still stands: it would be a great feature, but there is no single reliable strategy that works for every relationship type. The [sorting documentation](https://spatie.be/docs/laravel-query-builder/v7/features/sorting/) points at writing a custom `Sort` class instead, which is exactly what this package provides, already written and tested.

Ordering by how many related rows a record has, and ordering by a value that maps to a custom position (an enum whose natural order is not alphabetical), are the same story: recognised use cases with no dedicated support in the core, deliberately left to application code because the query shape depends on the domain.

This package gives you all three, plus builder macros for when you are not going through spatie at all.

## Installation

Install the package via Composer:

```bash
composer require plin-code/laravel-eloquent-sorts
```

The service provider is discovered automatically. Publish the config file if you want to change the default:

```bash
php artisan vendor:publish --tag="eloquent-sorts-config"
```

## `Orders\`: plain Eloquent, no spatie required

The SQL for all three sorts lives in three static classes under `PlinCode\EloquentSorts\Orders\`. They only touch `Illuminate\Database\Eloquent\Builder`, so they work in any Eloquent project, spatie or not.

Order books by their author's name:

```php
use PlinCode\EloquentSorts\Orders\RelationOrder;

RelationOrder::apply(
    query: Book::query(),
    relatedTable: 'authors',
    foreignKey: 'author_id',
    sortColumn: 'name',
    direction: 'asc',
);
```

Order authors by how many books they have:

```php
use PlinCode\EloquentSorts\Orders\RelationCountOrder;

RelationCountOrder::apply(
    query: Author::query(),
    relatedTable: 'books',
    foreignKey: 'author_id',
    direction: 'desc',
);
```

Order invoices by a status that is not alphabetical:

```php
use PlinCode\EloquentSorts\Orders\EnumOrder;

EnumOrder::apply(
    query: Invoice::query(),
    column: 'status',
    casesMap: ['draft' => 1, 'sent' => 2, 'paid' => 3],
);
```

## `Sorts\`: adapters for spatie/laravel-query-builder

`PlinCode\EloquentSorts\Sorts\` wraps each `Orders\` class in a one line `Spatie\QueryBuilder\Sorts\Sort` implementation, so it plugs straight into `AllowedSort::custom()`:

```php
use PlinCode\EloquentSorts\Sorts\RelationSorter;
use PlinCode\EloquentSorts\Sorts\RelationCountSorter;
use PlinCode\EloquentSorts\Sorts\EnumSorter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

QueryBuilder::for(Book::class)
    ->allowedSorts(
        AllowedSort::custom('author', new RelationSorter('authors', 'author_id')),
        AllowedSort::custom('author_book_count', new RelationCountSorter('books', 'author_id')),
        AllowedSort::custom('status', new EnumSorter(['draft' => 1, 'sent' => 2, 'paid' => 3])),
    )
    ->get();
```

`RelationSorter` and `RelationCountSorter` take the same parameters as their `Orders\` counterpart, minus `$query` and `$direction`: spatie supplies both when it invokes the sort. `EnumSorter` is the exception: its constructor takes `$casesMap` first and `$column` second, because `$column` is optional and, when omitted, falls back to the sort property spatie passes in. See the worked example below.

## Macros, and how to turn them off

When `config('eloquent-sorts.register_macros')` is true (the default), the service provider registers three macros on `Illuminate\Database\Eloquent\Builder`:

```php
Book::query()->orderByRelation('authors', 'author_id')->get();
Author::query()->orderByRelationCount('books', 'author_id')->get();
Invoice::query()->orderByEnum('status', ['draft' => 1, 'sent' => 2, 'paid' => 3])->get();
```

Set `ELOQUENT_SORTS_REGISTER_MACROS=false`, or `register_macros => false` in the published config, to turn them off. Turning them off only removes the macros: `Orders\` and `Sorts\` keep working exactly as before. The macros are one line wrappers over `Orders\`, nothing more, so there is no behaviour left behind to lose.

## Soft deletes

`RelationOrder`, `RelationCountOrder` and their `Sorts\` counterparts take an optional `$softDeleteColumn`, which defaults to `null`. With the default, no soft delete filter is applied at all: a soft deleted related row still counts, and its value can still be picked up by the correlated subquery. This is a deliberate change from the code this package was extracted from, which always filtered on `deleted_at` and broke against any related table that did not have that column.

Pass the column explicitly to get the filter back:

```php
RelationOrder::apply(
    query: Book::query(),
    relatedTable: 'authors',
    foreignKey: 'author_id',
    softDeleteColumn: 'deleted_at',
);
```

## The enum case

`EnumOrder` and `EnumSorter` take a plain `array<string|int, int>` map from value to sort position, so the package does not need to know anything about enums. A typical source for that map is a method on the enum itself:

```php
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';

    /** @return array<string, int> */
    public static function sortMap(): array
    {
        return [
            self::Draft->value => 1,
            self::Sent->value => 2,
            self::Paid->value => 3,
        ];
    }
}

EnumOrder::apply(Invoice::query(), 'status', InvoiceStatus::sortMap());
```

Values that are not in the map fall back to `$fallbackOrder` (99 by default), which keeps unknown values last instead of erroring.

An empty `$casesMap` leaves the query untouched: `EnumOrder::apply()` returns it unchanged, so `new EnumSorter([])` is a silent no-op sort when reached through spatie.

## Known limits

Table and column names passed to `Orders\`, `Sorts\` and the macros are not validated against the schema. A typo surfaces only at query time, as a `QueryException` from the underlying driver, not as a package level exception.

When a sort cannot resolve a value (a null foreign key, or a related row excluded by `softDeleteColumn`), that row sorts as `NULL`, and where `NULL` lands is dialect specific: PostgreSQL puts it last on ascending order, MySQL and SQLite put it first. The package does not normalize this, on purpose: hiding a driver difference behind a fixed position would be a silent assumption about a case the caller is better placed to decide.

`EnumOrder`'s `$column` is always resolved against the queried model's own table: any `table.` prefix is stripped and replaced with that table, never honoured as given. Passing a foreign prefix, such as `authors.status` on a `Book` query, silently sorts by `books.status` instead. This usually fails loudly as a `QueryException` when the queried table has no such column, but if both tables happen to carry a column of that name, the caller gets a wrong sort with no signal.

`RelationOrder`'s correlated subquery adds `limit(1)`, so when `$ownerKey` is not unique on the related table, the row picked to supply the sort value is arbitrary and driver dependent.

## Why this was not proposed upstream to spatie

Relation sorting, relation count sorting and enum ordering are not omissions in `spatie/laravel-query-builder`, they are use cases the maintainers looked at and left to application code, as the evidence above shows: [issue #36](https://github.com/spatie/laravel-query-builder/issues/36) for relation sorting closed without a merge, no dedicated issue for relation count sorting because the core only ever treats counting as an include (`withCount`), and the enum case is already the textbook example of the custom `Sort` pattern the core exposes on purpose. None of the three is a bug, so none of them is a candidate for a pull request.

The `orderByRelation`, `orderByRelationCount` and `orderByEnum` macros live on `Illuminate\Database\Eloquent\Builder`, which is not a class spatie's package touches at all, so they were never in scope for an upstream contribution either.

## Compatibility

| | Supported |
| --- | --- |
| PHP | 8.4, 8.5 |
| Laravel | 12, 13 (`illuminate/database` and `illuminate/support` `^12.0 \|\| ^13.0`) |
| spatie/laravel-query-builder | `^7.3.1`, not `^7.0` and not `^6.x`: `Sort` only became generic in 7.3.1, every 7.0.0 through 7.3.0 release ships a plain interface, and `Sorts\` relies on `@implements Sort<Model>` for static analysis |
| Drivers proved by the test suite | SQLite, MySQL 8, PostgreSQL 16 |

### Running the tests against the three drivers

The fast suite runs against an in memory SQLite database by default:

```bash
composer test
```

Tests marked `->group('integration')` exercise the correlated subqueries and identifier quoting against a real grammar. They can be pointed at a real MySQL or PostgreSQL instance with the same environment variables the CI workflow uses:

```bash
# against MySQL 8
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=testing DB_USERNAME=root DB_PASSWORD=root \
    vendor/bin/pest --group=integration

# against PostgreSQL 16
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=testing DB_USERNAME=postgres DB_PASSWORD=postgres \
    vendor/bin/pest --group=integration
```

CI runs the same two commands against `mysql:8` and `postgres:16` service containers; see `.github/workflows/integration-tests.yml`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Eloquent Sorts! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Daniele Barbaro](https://github.com/plin-code)
- [All Contributors](../../contributors)

## License

Laravel Eloquent Sorts is open-sourced software licensed under the [MIT license](LICENSE.md).
