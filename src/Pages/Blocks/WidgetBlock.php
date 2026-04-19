<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Pages\Blocks;

final class WidgetBlock
{
    public function __construct(public readonly mixed $widget) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'widget',
            'widget' => $this->widget->toArray(),
        ];
    }
}
