<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Dashboard;

use MaherElGamil\Rocket\Dashboard\Concerns\CanRenderOnPages;
use MaherElGamil\Rocket\Support\Concerns\HasColumnSpan;

final class ChartWidget
{
    use HasColumnSpan;
    use CanRenderOnPages;

    private string $chartType = 'line';

    private string $interval = 'day';

    private ?Closure $queryCallback = null;

    private string $dateColumn = 'created_at';

    private ?string $valueColumn = null;

    private string $color = '#2563eb';

    private ?int $limit = null;

    public function __construct(private readonly string $title) {}

    public static function make(string $title): self
    {
        return new self($title);
    }

    public function type(string $type): self
    {
        $this->chartType = $type;

        return $this;
    }

    public function interval(string $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function data(Closure $callback): self
    {
        $this->queryCallback = $callback;

        return $this;
    }

    public function dateColumn(string $column): self
    {
        $this->dateColumn = $column;

        return $this;
    }

    public function valueColumn(string $column): self
    {
        $this->valueColumn = $column;

        return $this;
    }

    public function color(string|Color $color): self
    {
        $this->color = $color instanceof Color ? $color->hex() : $color;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'chart',
            'chart_type' => $this->chartType,
            'title' => $this->title,
            'color' => $this->color,
            'column_span' => $this->columnSpan,
            'data' => $this->buildData(),
        ];
    }

    /**
     * @return array<int, array{label: string, value: float}>
     */
    private function buildData(): array
    {
        if ($this->queryCallback === null) {
            return [];
        }

        $builder = ($this->queryCallback)();
        $expr = $this->bucketExpression();
        $valueExpr = $this->valueColumn !== null
            ? "SUM({$this->valueColumn})"
            : 'COUNT(*)';

        $results = $builder
            ->selectRaw("{$expr} as bucket, {$valueExpr} as value")
            ->groupBy(DB::raw($expr))
            ->orderBy('bucket')
            ->pluck('value', 'bucket')
            ->toArray();

        return $this->fillBuckets($results);
    }

    private function bucketExpression(): string
    {
        $col = $this->dateColumn;
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => match ($this->interval) {
                'week' => "strftime('%Y-%W', {$col})",
                'month' => "strftime('%Y-%m', {$col})",
                default => "strftime('%Y-%m-%d', {$col})",
            },
            'pgsql' => match ($this->interval) {
                'week' => "TO_CHAR(DATE_TRUNC('week', {$col}), 'IYYY-IW')",
                'month' => "TO_CHAR({$col}, 'YYYY-MM')",
                default => "TO_CHAR({$col}::date, 'YYYY-MM-DD')",
            },
            default => match ($this->interval) {
                'week' => "DATE_FORMAT({$col}, '%Y-%u')",
                'month' => "DATE_FORMAT({$col}, '%Y-%m')",
                default => "DATE_FORMAT({$col}, '%Y-%m-%d')",
            },
        };
    }

    /**
     * @param  array<string, mixed>  $results
     * @return array<int, array{label: string, value: float}>
     */
    private function fillBuckets(array $results): array
    {
        $now = Carbon::now();
        $limit = $this->limit ?? match ($this->interval) {
            'month' => 12,
            'week' => 12,
            default => 30,
        };

        $buckets = [];

        for ($i = $limit - 1; $i >= 0; $i--) {
            [$key, $label] = match ($this->interval) {
                'week' => (function () use ($now, $i): array {
                    $date = $now->copy()->subWeeks($i);

                    return [$date->format('Y-W'), 'Wk '.(int) $date->format('W')];
                })(),
                'month' => (function () use ($now, $i): array {
                    $date = $now->copy()->subMonths($i);

                    return [$date->format('Y-m'), $date->format('M Y')];
                })(),
                default => (function () use ($now, $i): array {
                    $date = $now->copy()->subDays($i);

                    return [$date->format('Y-m-d'), $date->format('M j')];
                })(),
            };

            $buckets[] = [
                'label' => $label,
                'value' => (float) ($results[$key] ?? 0),
            ];
        }

        return $buckets;
    }
}
