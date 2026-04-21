<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $importer
 * @property string $file_name
 * @property string $file_disk
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $successful_rows
 * @property int $failed_rows
 * @property ?string $batch_id
 * @property ?int $user_id
 * @property ?\Illuminate\Support\Carbon $completed_at
 */
class Import extends Model
{
    protected $table = 'rocket_imports';

    protected $guarded = [];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');

        return $this->belongsTo($userModel);
    }

    public function failedRows(): HasMany
    {
        return $this->hasMany(FailedImportRow::class, 'import_id');
    }

    public function getStatus(): string
    {
        if ($this->completed_at !== null) {
            return 'completed';
        }

        return 'processing';
    }

    public function getProgress(): int
    {
        if ($this->total_rows <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->processed_rows / $this->total_rows) * 100));
    }
}
