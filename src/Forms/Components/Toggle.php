<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

final class Toggle extends Field
{
    public function type(): string
    {
        return 'toggle';
    }

    protected function typeRules(): array
    {
        return ['boolean'];
    }
}
