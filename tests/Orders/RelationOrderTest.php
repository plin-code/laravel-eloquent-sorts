<?php

declare(strict_types=1);

use PlinCode\EloquentSorts\Orders\RelationOrder;
use Workbench\App\Models\Author;
use Workbench\App\Models\Book;
use Workbench\App\Models\Tag;

function seedAuthorsAndBooks(): void
{
    $calvino = Author::create(['name' => 'Calvino']);
    $buzzati = Author::create(['name' => 'Buzzati']);

    Book::create(['author_id' => $calvino->id, 'status' => 'c']);
    Book::create(['author_id' => $buzzati->id, 'status' => 'b']);
}

it('orders by a column of the related table, ascending', function () {
    seedAuthorsAndBooks();

    $query = Book::query();
    RelationOrder::apply($query, 'authors', 'author_id');

    expect($query->pluck('status')->all())->toBe(['b', 'c']);
})->group('integration');

it('orders by a column of the related table, descending', function () {
    seedAuthorsAndBooks();

    $query = Book::query();
    RelationOrder::apply($query, 'authors', 'author_id', 'name', 'desc');

    expect($query->pluck('status')->all())->toBe(['c', 'b']);
})->group('integration');

it('orders by a custom sort column', function () {
    $a = Author::create(['name' => 'zzz']);
    $b = Author::create(['name' => 'aaa']);
    Book::create(['author_id' => $a->id, 'status' => 'first_inserted']);
    Book::create(['author_id' => $b->id, 'status' => 'second_inserted']);

    $query = Book::query();
    RelationOrder::apply($query, 'authors', 'author_id', 'id');

    expect($query->pluck('status')->all())->toBe(['first_inserted', 'second_inserted']);
})->group('integration');

it('uses a custom owner key on the related table', function () {
    Tag::create(['code' => 'zz', 'label' => 'Zoology']);
    Tag::create(['code' => 'aa', 'label' => 'Architecture']);
    Book::create(['tag_code' => 'zz', 'status' => 'zoology book']);
    Book::create(['tag_code' => 'aa', 'status' => 'architecture book']);

    $query = Book::query();
    RelationOrder::apply($query, 'tags', 'tag_code', 'label', 'asc', 'code');

    expect($query->pluck('status')->all())->toBe(['architecture book', 'zoology book']);
})->group('integration');

it('keeps rows whose foreign key is null, wherever the driver puts them', function () {
    $author = Author::create(['name' => 'Calvino']);
    Book::create(['author_id' => $author->id, 'status' => 'has author']);
    Book::create(['author_id' => null, 'status' => 'orphan']);

    $query = Book::query();
    RelationOrder::apply($query, 'authors', 'author_id');

    // Dove finisce un NULL dipende dal driver: PostgreSQL lo mette in coda su
    // ASC, MySQL e SQLite in testa. Il pacchetto non normalizza la cosa, quindi
    // il test asserisce che la riga non sparisca, non dove finisca.
    expect($query->pluck('status')->all())
        ->toHaveCount(2)
        ->toContain('has author', 'orphan');
})->group('integration');

it('stops resolving a sort value when the related row is soft deleted', function () {
    Tag::create(['code' => 'aa', 'label' => 'Architecture']);
    Tag::create(['code' => 'zz', 'label' => 'Zoology', 'deleted_at' => now()]);
    Book::create(['tag_code' => 'aa', 'status' => 'visible tag']);
    Book::create(['tag_code' => 'zz', 'status' => 'deleted tag']);

    $query = Book::query();
    RelationOrder::apply($query, 'tags', 'tag_code', 'label', 'asc', 'code', 'deleted_at');

    // Il tag cancellato non produce piu' un valore, quindi la sua riga ordina
    // per NULL, e la posizione del NULL e' dialect specific. Vedi il test qui
    // sopra: e' un limite dichiarato del pacchetto, non un bug.
    $expected = driver() === 'pgsql'
        ? ['visible tag', 'deleted tag']
        : ['deleted tag', 'visible tag'];

    expect($query->pluck('status')->all())->toBe($expected);
})->group('integration');

it('applies no soft delete filter by default', function () {
    $query = Book::query();
    RelationOrder::apply($query, 'authors', 'author_id');

    expect($query->toSql())->not->toContain('deleted_at');
});

it('adds the soft delete filter only when asked', function () {
    $query = Book::query();
    RelationOrder::apply($query, 'authors', 'author_id', 'name', 'asc', 'id', 'deleted_at');

    expect($query->toSql())->toContain('deleted_at');
});

it('rejects a direction that is not asc or desc', function () {
    RelationOrder::apply(Book::query(), 'authors', 'author_id', 'name', 'sideways');
})->throws(InvalidArgumentException::class);
