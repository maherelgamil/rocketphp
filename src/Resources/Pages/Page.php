<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Resources\Pages;

use Illuminate\Http\Request;
use Inertia\Response;
use MaherElGamil\Rocket\Panel\Panel;

abstract class Page
{
    /**
     * @param  class-string<\MaherElGamil\Rocket\Resources\Resource>  $resource
     */
    abstract public function handle(Request $request, Panel $panel, string $resource): Response;

    public static function component(): string
    {
        return 'rocket/'.class_basename(static::class);
    }
}
