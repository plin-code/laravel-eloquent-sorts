<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $code
 * @property string $label
 * @property string|null $deleted_at
 */
class Tag extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'tags';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $guarded = [];
}
