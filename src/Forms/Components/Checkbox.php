<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

final class Checkbox extends Field
{
    public function type(): string
    {
        return 'checkbox';
    }

    protected function typeRules(): array
    {
        return ['boolean'];
    }
}
