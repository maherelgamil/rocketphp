<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters\Constraints;

use Illuminate\Database\Eloquent\Builder;

final class TextConstraint extends Constraint
{
    public function operators(): array
    {
        return [
            'equals' => 'Equals',
            'not_equals' => 'Does not equal',
            'contains' => 'Contains',
            'not_contains' => 'Does not contain',
            'starts_with' => 'Starts with',
            'ends_with' => 'Ends with',
            'is_empty' => 'Is empty',
            'is_not_empty' => 'Is not empty',
        ];
    }

    public function inputType(string $operator): string
    {
        return in_array($operator, ['is_empty', 'is_not_empty'], true) ? 'none' : 'text';
    }

    public function apply(Builder $query, string $operator, $value, string $boolean = 'and'): void
    {
        $col = $this->getAttribute();
        $bool = $boolean === 'or' ? 'or' : 'and';
        $valueIsEmpty = $value === null || $value === '';

        if (! in_array($operator, ['is_empty', 'is_not_empty'], true) && $valueIsEmpty) {
            return;
        }

        $where = $bool === 'or' ? 'orWhere' : 'where';

        match ($operator) {
            'equals' => $query->{$where}($col, '=', $value),
            'not_equals' => $query->{$where}($col, '!=', $value),
            'contains' => $query->{$where}($col, 'like', '%'.$value.'%'),
            'not_contains' => $query->{$where}($col, 'not like', '%'.$value.'%'),
            'starts_with' => $query->{$where}($col, 'like', $value.'%'),
            'ends_with' => $query->{$where}($col, 'like', '%'.$value),
            'is_empty' => $query->{$where}(function (Builder $q) use ($col): void {
                $q->whereNull($col)->orWhere($col, '');
            }),
            'is_not_empty' => $query->{$where}(function (Builder $q) use ($col): void {
                $q->whereNotNull($col)->where($col, '!=', '');
            }),
            default => null,
        };
    }
}
