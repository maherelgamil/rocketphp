<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Dashboard;

use MaherElGamil\Rocket\Dashboard\Concerns\CanRenderOnPages;
use MaherElGamil\Rocket\Support\Concerns\HasColumnSpan;

final class StatWidget
{
    use HasColumnSpan;
    use CanRenderOnPages;

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
            'column_span' => $this->columnSpan,
        ];
    }
}
