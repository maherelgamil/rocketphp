<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Columns;

final class BadgeColumn extends Column
{
    /** @var array<string, string> */
    private array $colors = [];

    /**
     * @param  array<string, string>  $colors
     */
    public function colors(array $colors): self
    {
        $this->colors = $colors;

        return $this;
    }

    public function type(): string
    {
        return 'badge';
    }

    protected function extraProps(): array
    {
        return [
            'colors' => $this->colors,
        ];
    }
}
