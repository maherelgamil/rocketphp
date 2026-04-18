<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Columns;

final class TextColumn extends Column
{
    private bool $copyable = false;

    public function copyable(bool $copyable = true): self
    {
        $this->copyable = $copyable;

        return $this;
    }

    public function type(): string
    {
        return 'text';
    }

    protected function extraProps(): array
    {
        return [
            'copyable' => $this->copyable,
        ];
    }
}
