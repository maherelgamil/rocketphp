<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters\Constraints;

use Illuminate\Database\Eloquent\Builder;

final class DateConstraint extends Constraint
{
    public function operators(): array
    {
        return [
            'on' => 'On',
            'before' => 'Before',
            'after' => 'After',
            'between' => 'Between',
            'is_null' => 'Is empty',
            'is_not_null' => 'Is not empty',
        ];
    }

    public function inputType(string $operator): string
    {
        return match ($operator) {
            'is_null', 'is_not_null' => 'none',
            'between' => 'date_range',
            default => 'date',
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
                if (! is_string($from) || $from === '' || ! is_string($to) || $to === '') {
                    return;
                }
                $method = $where.'Between';
                $query->{$method}($col, [$from, $to]);

                return;
            }
            if (! is_string($value) || $value === '') {
                return;
            }
        }

        match ($operator) {
            'on' => $query->{$where.'Date'}($col, '=', $value),
            'before' => $query->{$where.'Date'}($col, '<', $value),
            'after' => $query->{$where.'Date'}($col, '>', $value),
            'is_null' => $boolean === 'or' ? $query->orWhereNull($col) : $query->whereNull($col),
            'is_not_null' => $boolean === 'or' ? $query->orWhereNotNull($col) : $query->whereNotNull($col),
            default => null,
        };
    }
}
