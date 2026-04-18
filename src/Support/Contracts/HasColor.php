<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Support\Contracts;

use MaherElGamil\Rocket\Support\Color;

interface HasColor
{
    public function getColor(): Color|string|null;
}
