<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $author_id
 * @property string|null $tag_code
 * @property string|null $status
 * @property string|null $order
 */
class Book extends Model
{
    public $timestamps = false;

    protected $table = 'books';

    protected $guarded = [];

    /** @return BelongsTo<Author, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
