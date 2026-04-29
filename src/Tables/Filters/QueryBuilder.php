<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Tables\Filters\Constraints\Constraint;

/**
 * Advanced filter with nested AND/OR rule groups.
 *
 * State shape:
 * {
 *   logic: 'and' | 'or',
 *   rules: [
 *     { column: string, operator: string, value: mixed },
 *     { logic: 'and' | 'or', rules: [...] }
 *   ]
 * }
 */
final class QueryBuilder extends Filter
{
    /** @var array<string, Constraint> */
    private array $constraints = [];

    /**
     * @param  array<int, Constraint>  $constraints
     */
    public function constraints(array $constraints): self
    {
        foreach ($constraints as $c) {
            $this->constraints[$c->getName()] = $c;
        }

        return $this;
    }

    public function getConstraint(string $name): ?Constraint
    {
        return $this->constraints[$name] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function constraintsToSchema(): array
    {
        return array_values(array_map(static fn (Constraint $c) => $c->toSchema(), $this->constraints));
    }

    /**
     * @return array{logic: string, rules: array<int, mixed>}
     */
    public function getState(Request $request): array
    {
        $raw = $request->input("filters.{$this->name}");

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($raw)) {
            $raw = $this->persistInSession
                ? session(self::sessionKey($request, $this->name))
                : null;
        }

        if (! is_array($raw) || ! isset($raw['rules'])) {
            return ['logic' => 'and', 'rules' => []];
        }

        $logic = ($raw['logic'] ?? 'and') === 'or' ? 'or' : 'and';

        return [
            'logic' => $logic,
            'rules' => is_array($raw['rules']) ? $raw['rules'] : [],
        ];
    }

    public function apply(Builder $query, Request $request): void
    {
        $state = $this->getState($request);

        if ($this->persistInSession) {
            $hasRules = is_array($state['rules']) && $state['rules'] !== [];
            $key = self::sessionKey($request, $this->name);
            if ($hasRules) {
                session([$key => $state]);
            } else {
                session()->forget($key);
            }
        }

        if ($state['rules'] === []) {
            return;
        }

        $self = $this;
        $query->where(function (Builder $q) use ($state, $self): void {
            $self->applyGroup($q, $state, 'and');
        });
    }

    /**
     * @param  array{logic: string, rules: array<int, mixed>}  $group
     */
    private function applyGroup(Builder $query, array $group, string $boolean): void
    {
        $logic = $group['logic'] === 'or' ? 'or' : 'and';

        foreach ($group['rules'] as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            // Nested group
            if (isset($rule['rules']) && is_array($rule['rules'])) {
                $where = $logic === 'or' ? 'orWhere' : 'where';
                $self = $this;
                $query->{$where}(function (Builder $q) use ($rule, $self): void {
                    $self->applyGroup($q, [
                        'logic' => ($rule['logic'] ?? 'and') === 'or' ? 'or' : 'and',
                        'rules' => $rule['rules'],
                    ], 'and');
                });

                continue;
            }

            $columnName = $rule['column'] ?? null;
            $operator = $rule['operator'] ?? null;
            $value = $rule['value'] ?? null;

            if (! is_string($columnName) || ! is_string($operator)) {
                continue;
            }

            $constraint = $this->constraints[$columnName] ?? null;
            if ($constraint === null) {
                continue;
            }

            $operators = $constraint->operators();
            if (! array_key_exists($operator, $operators)) {
                continue;
            }

            $constraint->apply($query, $operator, $value, $logic);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array
    {
        $state = $this->getState($request);

        $indicators = [];
        $ruleCount = $this->countRules($state['rules']);
        if ($ruleCount > 0) {
            $indicators = $this->buildIndicators($request, [[
                'label' => $this->getLabel().' ('.$ruleCount.')',
                'clear_keys' => ["filters.{$this->name}"],
            ]]);
        }

        return [
            'type' => 'query_builder',
            'name' => $this->name,
            'label' => $this->getLabel(),
            'state_key' => "filters.{$this->name}",
            'state' => $state,
            'constraints' => $this->constraintsToSchema(),
            'visible_in_dropdown' => $this->visibleInDropdown,
            'active_indicators' => $indicators,
            'rule_count' => $ruleCount,
        ];
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    private function countRules(array $rules): int
    {
        $count = 0;
        foreach ($rules as $r) {
            if (! is_array($r)) {
                continue;
            }
            if (isset($r['rules']) && is_array($r['rules'])) {
                $count += $this->countRules($r['rules']);

                continue;
            }
            if (isset($r['column'], $r['operator'])) {
                $count++;
            }
        }

        return $count;
    }
}
