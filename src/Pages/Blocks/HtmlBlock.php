<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Pages\Blocks;

final class HtmlBlock
{
    public function __construct(public readonly string $html) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'html',
            'html' => $this->html,
        ];
    }
}
