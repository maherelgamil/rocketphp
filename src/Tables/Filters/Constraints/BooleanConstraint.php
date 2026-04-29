<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters\Constraints;

use Illuminate\Database\Eloquent\Builder;

final class BooleanConstraint extends Constraint
{
    public function operators(): array
    {
        return [
            'is_true' => 'Is true',
            'is_false' => 'Is false',
        ];
    }

    public function inputType(string $operator): string
    {
        return 'none';
    }

    public function apply(Builder $query, string $operator, $value, string $boolean = 'and'): void
    {
        $col = $this->getAttribute();
        $where = $boolean === 'or' ? 'orWhere' : 'where';

        match ($operator) {
            'is_true' => $query->{$where}($col, true),
            'is_false' => $query->{$where}($col, false),
            default => null,
        };
    }
}
