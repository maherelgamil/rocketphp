<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class Widget extends Model
{
    protected $guarded = [];

    public $timestamps = false;
}
