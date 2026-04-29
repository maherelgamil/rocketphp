<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters\Constraints;

use Illuminate\Database\Eloquent\Builder;
use MaherElGamil\Rocket\Support\EnumSupport;

final class SelectConstraint extends Constraint
{
    /** @var array<string, string> */
    private array $options = [];

    public function options(): ?array
    {
        return $this->options === [] ? null : $this->options;
    }

    /**
     * @param  array<string, string>  $options
     */
    public function withOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  class-string  $enumClass
     */
    public function enum(string $enumClass): static
    {
        $this->options = EnumSupport::toOptions($enumClass);

        return $this;
    }

    public function operators(): array
    {
        return [
            'in' => 'Is one of',
            'not_in' => 'Is not one of',
            'is_null' => 'Is empty',
            'is_not_null' => 'Is not empty',
        ];
    }

    public function inputType(string $operator): string
    {
        return match ($operator) {
            'is_null', 'is_not_null' => 'none',
            default => 'multi_select',
        };
    }

    public function apply(Builder $query, string $operator, $value, string $boolean = 'and'): void
    {
        $col = $this->getAttribute();
        $where = $boolean === 'or' ? 'orWhere' : 'where';

        if (in_array($operator, ['in', 'not_in'], true)) {
            if (! is_array($value) || $value === []) {
                return;
            }
            $allowed = array_keys($this->options);
            // Whitelist values against the registered options to keep it safe.
            if ($allowed !== []) {
                $value = array_values(array_intersect($value, $allowed));
                if ($value === []) {
                    return;
                }
            }
        }

        match ($operator) {
            'in' => $query->{$where.'In'}($col, $value),
            'not_in' => $query->{$where.'NotIn'}($col, $value),
            'is_null' => $boolean === 'or' ? $query->orWhereNull($col) : $query->whereNull($col),
            'is_not_null' => $boolean === 'or' ? $query->orWhereNotNull($col) : $query->whereNotNull($col),
            default => null,
        };
    }
}
