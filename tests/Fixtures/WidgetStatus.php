<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use MaherElGamil\Rocket\Support\Color;
use MaherElGamil\Rocket\Support\Contracts\HasColor;
use MaherElGamil\Rocket\Support\Contracts\HasLabel;

enum WidgetStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Draft = 'draft';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active Widget',
            self::Draft => 'Draft Widget',
            self::Archived => 'Archived Widget',
        };
    }

    public function getColor(): Color|string|null
    {
        return match ($this) {
            self::Active => Color::Green,
            self::Draft => Color::Slate,
            self::Archived => null,
        };
    }
}
