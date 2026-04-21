<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $exporter
 * @property string $file_name
 * @property string $file_disk
 * @property int $total_rows
 * @property int $successful_rows
 * @property ?string $batch_id
 * @property ?int $user_id
 * @property ?\Illuminate\Support\Carbon $completed_at
 */
class Export extends Model
{
    protected $table = 'rocket_exports';

    protected $guarded = [];

    protected $casts = [
        'total_rows' => 'integer',
        'successful_rows' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');

        return $this->belongsTo($userModel);
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

        return min(100, (int) round(($this->successful_rows / $this->total_rows) * 100));
    }
}
