<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Forms\Components\Field;

/**
 * A filter whose UI is rendered from a Form field schema.
 *
 * Filter::make('created_between')
 *     ->form([
 *         DatePicker::make('from'),
 *         DatePicker::make('until'),
 *     ])
 *     ->query(function (Builder $q, array $data) {
 *         $q->when($data['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v));
 *         $q->when($data['until'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
 *     });
 */
final class CustomFilter extends Filter
{
    /** @var array<int, Field> */
    private array $fields = [];

    private ?Closure $queryCallback = null;

    /**
     * @param  array<int, Field>  $fields
     */
    public function form(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function query(Closure $callback): self
    {
        $this->queryCallback = $callback;

        return $this;
    }

    /**
     * Convenience: a single-checkbox toggle filter. Equivalent to
     * ->form([Checkbox::make($name)])->query(fn ($q, $data) => ...).
     */
    public function toggle(): self
    {
        // The frontend renders this as a single-checkbox shortcut; backend just
        // applies the user's query() callback when the value is truthy.
        $this->fields = [];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(Request $request): array
    {
        $state = [];
        if ($this->fields === []) {
            // Toggle / no fields — read a single nested value at filters.{name}.
            $value = $this->readState($request);
            $state['__value'] = $value;

            return $state;
        }

        foreach ($this->fields as $field) {
            $key = $field->getName();
            $legacy = 'filter_'.$this->name.'_'.$key;
            $state[$key] = $this->readState($request, $key, $legacy);
        }

        return $state;
    }

    public function apply(Builder $query, Request $request): void
    {
        $state = $this->getState($request);
        $this->persistState($request, $state);

        if ($this->queryCallback === null) {
            return;
        }

        $hasValue = false;
        foreach ($state as $v) {
            if ($v !== null && $v !== '' && $v !== false && $v !== []) {
                $hasValue = true;
                break;
            }
        }

        if (! $hasValue) {
            return;
        }

        ($this->queryCallback)($query, $state, $request);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array
    {
        $state = $this->getState($request);
        $stateKeys = [];
        $legacyKeys = [];
        $form = [];

        if ($this->fields === []) {
            $stateKeys[] = "filters.{$this->name}";
            $legacyKeys[] = 'filter_'.$this->name;
        } else {
            foreach ($this->fields as $field) {
                $form[] = $field->toArray();
                $stateKeys[] = "filters.{$this->name}.".$field->getName();
                $legacyKeys[] = 'filter_'.$this->name.'_'.$field->getName();
            }
        }

        $indicators = [];
        $hasValue = false;
        foreach ($state as $v) {
            if ($v !== null && $v !== '' && $v !== false && $v !== []) {
                $hasValue = true;
                break;
            }
        }
        if ($hasValue) {
            $indicators = $this->buildIndicators($request, [[
                'label' => $this->getLabel(),
                'clear_keys' => array_merge($stateKeys, $legacyKeys),
            ]]);
        }

        return [
            'type' => $this->fields === [] ? 'toggle' : 'custom',
            'name' => $this->name,
            'label' => $this->getLabel(),
            'state_namespace' => "filters.{$this->name}",
            'state' => $state,
            'form' => $form,
            'visible_in_dropdown' => $this->visibleInDropdown,
            'active_indicators' => $indicators,
        ];
    }
}
