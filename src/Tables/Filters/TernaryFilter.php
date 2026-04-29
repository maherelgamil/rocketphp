<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Tri-state filter for boolean columns: all (default), yes, no.
 */
final class TernaryFilter extends Filter
{
    private string $attribute;

    public function __construct(
        string $name,
        ?string $attribute = null,
        ?string $label = null,
    ) {
        parent::__construct($name);
        $this->attribute = $attribute ?? $name;
        if ($label !== null) {
            $this->label = $label;
        }
    }

    public function attribute(string $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function apply(Builder $query, Request $request): void
    {
        $value = $this->readState($request);
        if ($value === null || $value === '' || $value === 'all') {
            $this->persistState($request, ['__value' => null]);

            return;
        }

        $this->persistState($request, ['__value' => $value]);

        if ($value === '1' || $value === 'true' || $value === 'yes') {
            $query->where($this->attribute, true);

            return;
        }

        if ($value === '0' || $value === 'false' || $value === 'no') {
            $query->where($this->attribute, false);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array
    {
        $value = $this->readState($request);
        $indicators = [];
        if ($value !== null && $value !== '' && $value !== 'all') {
            $label = match ((string) $value) {
                '1', 'true', 'yes' => 'Yes',
                '0', 'false', 'no' => 'No',
                default => (string) $value,
            };
            $indicators = $this->buildIndicators($request, [[
                'label' => $this->getLabel().': '.$label,
                'clear_keys' => ["filters.{$this->name}", $this->legacyKey()],
            ]]);
        }

        return [
            'type' => 'ternary',
            'name' => $this->name,
            'query_key' => $this->legacyKey(),
            'state_key' => "filters.{$this->name}",
            'label' => $this->getLabel(),
            'value' => $value ?? 'all',
            'visible_in_dropdown' => $this->visibleInDropdown,
            'active_indicators' => $indicators,
        ];
    }

    private function legacyKey(): string
    {
        return 'filter_'.$this->name;
    }
}
