<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Support\Concerns;

trait HasColumnSpan
{
    private int|string $columnSpan = 1;

    public function columnSpan(int|string $span): static
    {
        $this->columnSpan = $span;

        return $this;
    }

    public function getColumnSpan(): int|string
    {
        return $this->columnSpan;
    }
}
