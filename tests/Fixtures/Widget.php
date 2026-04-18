<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Widget extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public $timestamps = false;

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'widget_tag');
    }
}
