<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters\Constraints;

use Illuminate\Database\Eloquent\Builder;

final class NumberConstraint extends Constraint
{
    public function operators(): array
    {
        return [
            'equals' => '=',
            'not_equals' => '≠',
            'gt' => '>',
            'gte' => '≥',
            'lt' => '<',
            'lte' => '≤',
            'between' => 'Between',
            'is_null' => 'Is null',
            'is_not_null' => 'Is not null',
        ];
    }

    public function inputType(string $operator): string
    {
        return match ($operator) {
            'is_null', 'is_not_null' => 'none',
            'between' => 'number_range',
            default => 'number',
        };
    }

    public function apply(Builder $query, string $operator, $value, string $boolean = 'and'): void
    {
        $col = $this->getAttribute();
        $where = $boolean === 'or' ? 'orWhere' : 'where';

        if (! in_array($operator, ['is_null', 'is_not_null'], true)) {
            if ($operator === 'between') {
                if (! is_array($value)) {
                    return;
                }
                $from = $value['from'] ?? $value[0] ?? null;
                $to = $value['to'] ?? $value[1] ?? null;
                if ($from === null || $to === null || $from === '' || $to === '') {
                    return;
                }
                $method = $where.'Between';
                $query->{$method}($col, [(float) $from, (float) $to]);

                return;
            }
            if ($value === null || $value === '') {
                return;
            }
        }

        match ($operator) {
            'equals' => $query->{$where}($col, '=', (float) $value),
            'not_equals' => $query->{$where}($col, '!=', (float) $value),
            'gt' => $query->{$where}($col, '>', (float) $value),
            'gte' => $query->{$where}($col, '>=', (float) $value),
            'lt' => $query->{$where}($col, '<', (float) $value),
            'lte' => $query->{$where}($col, '<=', (float) $value),
            'is_null' => $boolean === 'or' ? $query->orWhereNull($col) : $query->whereNull($col),
            'is_not_null' => $boolean === 'or' ? $query->orWhereNotNull($col) : $query->whereNotNull($col),
            default => null,
        };
    }
}
