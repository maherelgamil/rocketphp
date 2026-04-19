<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Pages\Blocks;

final class GridBlock
{
    /**
     * @param  array<int, mixed>  $blocks
     */
    public function __construct(
        public readonly array $blocks,
        public readonly int $columns = 2,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'grid',
            'columns' => $this->columns,
            'blocks' => array_map(fn ($b) => $b->toArray(), $this->blocks),
        ];
    }
}
