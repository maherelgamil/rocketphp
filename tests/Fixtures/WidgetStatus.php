<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

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

    public function getColor(): ?string
    {
        return match ($this) {
            self::Active => '#16a34a',
            self::Draft => '#64748b',
            self::Archived => null,
        };
    }
}
