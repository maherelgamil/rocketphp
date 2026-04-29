<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Dashboard;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use MaherElGamil\Rocket\Support\Concerns\HasColumnSpan;

final class ActivityFeedWidget
{
    use HasColumnSpan;

    private ?Closure $queryCallback = null;

    private string $titleColumn = 'description';

    private string $timeColumn = 'created_at';

    private ?string $iconColumn = null;

    public function __construct(private readonly string $title) {}

    public static function make(string $title): self
    {
        return new self($title);
    }

    public function query(Closure $callback): self
    {
        $this->queryCallback = $callback;

        return $this;
    }

    public function titleColumn(string $column): self
    {
        $this->titleColumn = $column;

        return $this;
    }

    public function timeColumn(string $column): self
    {
        $this->timeColumn = $column;

        return $this;
    }

    public function iconColumn(string $column): self
    {
        $this->iconColumn = $column;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'activity_feed',
            'title' => $this->title,
            'column_span' => $this->columnSpan,
            'items' => $this->buildItems(),
        ];
    }

    /**
     * @return array<int, array{title: string, time: string|null, icon: string}>
     */
    private function buildItems(): array
    {
        if ($this->queryCallback === null) {
            return [];
        }

        $rows = ($this->queryCallback)();

        $collection = is_iterable($rows) && ! ($rows instanceof Collection)
            ? collect($rows)
            : $rows;

        return $collection->map(function ($row) {
            $arr = match (true) {
                is_array($row) => $row,
                $row instanceof Arrayable => $row->toArray(),
                default => (array) $row,
            };

            $icon = 'activity';
            if ($this->iconColumn !== null && isset($arr[$this->iconColumn])) {
                $icon = (string) $arr[$this->iconColumn];
            }

            return [
                'title' => (string) ($arr[$this->titleColumn] ?? ''),
                'time' => isset($arr[$this->timeColumn]) ? (string) $arr[$this->timeColumn] : null,
                'icon' => $icon,
            ];
        })->values()->all();
    }
}
