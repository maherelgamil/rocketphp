<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $import_id
 * @property array<string, mixed> $data
 * @property string $validation_error
 */
class FailedImportRow extends Model
{
    protected $table = 'rocket_failed_import_rows';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'import_id');
    }
}
