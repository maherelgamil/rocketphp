<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Dashboard;

final class StatWidget
{
    public function __construct(
        private readonly string $label,
        private readonly string|int|float $value,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'stat',
            'label' => $this->label,
            'value' => $this->value,
        ];
    }
}
