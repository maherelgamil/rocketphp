<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Facades;

use Illuminate\Support\Facades\Facade;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;

/**
 * @method static Panel panel(string $id)
 * @method static Panel register(Panel $panel)
 * @method static Panel get(string $id)
 * @method static Panel getCurrent()
 * @method static void setCurrent(string $id)
 * @method static array all()
 */
final class Rocket extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PanelManager::class;
    }
}
