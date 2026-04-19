<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Dashboard;

use MaherElGamil\Rocket\Support\Concerns\HasColumnSpan;

final class TableWidget
{
    use HasColumnSpan;

    /**
     * @param  array<int, array{name: string, label: string}>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        private readonly string $title,
        private readonly array $columns,
        private readonly array $rows,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'table',
            'title' => $this->title,
            'column_span' => $this->columnSpan,
            'columns' => $this->columns,
            'rows' => $this->rows,
        ];
    }
}
