# Release Notes

## [Unreleased](https://github.com/plin-code/laravel-eloquent-sorts/compare/v1.0.0...HEAD)

## [v1.0.0](https://github.com/plin-code/laravel-eloquent-sorts/compare/v1.0.0...v1.0.0) - 2026-09-02

First stable release.

Reusable Eloquent sorts for Laravel: order by a column on a related table, by a count of related rows, or by a custom enum order. Usable as plain Eloquent, as `Illuminate\Database\Eloquent\Builder` macros, or as `spatie/laravel-query-builder` sorts.

### What is in it

The SQL lives in one place, three static classes under `Orders\` that are pure Eloquent and never touch spatie:

- `Orders\RelationOrder::apply()` sorts by a column on a related table through a correlated subquery.
- `Orders\RelationCountOrder::apply()` sorts by how many related rows a row has, counting zero rather than dropping the row.
- `Orders\EnumOrder::apply()` sorts by an arbitrary map of values to positions, with a fallback for values the map does not cover.

Everything else wraps them in one line:

- Three `Sorts\` classes implementing `Spatie\QueryBuilder\Sorts\Sort`, for `AllowedSort::custom()`.
- Three macros on `Illuminate\Database\Eloquent\Builder`: `orderByRelation`, `orderByCount`, `orderByEnum`.

Setting `eloquent-sorts.register_macros` to false disables the macros and nothing else. The `Orders\` and `Sorts\` classes keep working, so a project that does not want global macros on the query builder can use the package without them.

### Notes for anyone porting similar code

This package was extracted from an application, and five behaviors were corrected on the way out. If you are migrating from hand rolled versions of these sorts, these are the differences to expect:

- Soft delete filtering is opt in. `softDeleteColumn` defaults to `null` and applies no filter. Pass `'deleted_at'` explicitly where you want it, rather than assuming every table has the column.
- Enum map values travel as query bindings instead of being interpolated into the SQL string.
- The sort direction is validated against `asc` and `desc`, and throws `InvalidArgumentException` otherwise. The raw SQL path had no validation of its own.
- Identifiers in the enum sort go through the query grammar, so a column named with a reserved word works.
- The related table's primary key is a parameter, `ownerKey`, instead of being hardcoded to `id`.

### Requirements

PHP 8.4 or 8.5. Laravel 12 or 13. `spatie/laravel-query-builder` `^7.3.1`, which is where `Sort` became generic, and only needed if you use the `Sorts\` adapters.

The suite runs against SQLite, MySQL 8 and PostgreSQL 16.
