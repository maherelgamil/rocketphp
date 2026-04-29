<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters\Constraints;

use Illuminate\Database\Eloquent\Builder;

final class RelationshipConstraint extends Constraint
{
    public function operators(): array
    {
        return [
            'has' => 'Has any',
            'doesnt_have' => 'Has none',
            'has_count_gt' => 'Has more than',
            'has_count_lt' => 'Has fewer than',
        ];
    }

    public function inputType(string $operator): string
    {
        return match ($operator) {
            'has', 'doesnt_have' => 'none',
            default => 'number',
        };
    }

    public function apply(Builder $query, string $operator, $value, string $boolean = 'and'): void
    {
        $relation = $this->getAttribute();
        $bool = $boolean === 'or' ? 'or' : 'and';

        match ($operator) {
            'has' => $query->has($relation, '>=', 1, $bool),
            'doesnt_have' => $query->doesntHave($relation, $bool),
            'has_count_gt' => is_numeric($value) ? $query->has($relation, '>', (int) $value, $bool) : null,
            'has_count_lt' => is_numeric($value) && (int) $value > 0
                ? $query->has($relation, '<', (int) $value, $bool)
                : null,
            default => null,
        };
    }
}
