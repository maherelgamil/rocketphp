<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class DateRangeFilter extends Filter
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
        $from = $this->readState($request, 'from', $this->fromKey());
        $until = $this->readState($request, 'until', $this->untilKey());

        $this->persistState($request, [
            'from' => is_string($from) && $from !== '' ? $from : null,
            'until' => is_string($until) && $until !== '' ? $until : null,
        ]);

        if (is_string($from) && $from !== '') {
            $query->whereDate($this->attribute, '>=', $from);
        }

        if (is_string($until) && $until !== '') {
            $query->whereDate($this->attribute, '<=', $until);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array
    {
        $from = $this->readState($request, 'from', $this->fromKey());
        $until = $this->readState($request, 'until', $this->untilKey());

        $indicatorEntries = [];
        if (is_string($from) && $from !== '' && is_string($until) && $until !== '') {
            $indicatorEntries[] = [
                'label' => $this->getLabel().': '.$from.' → '.$until,
                'clear_keys' => $this->allClearKeys(),
            ];
        } elseif (is_string($from) && $from !== '') {
            $indicatorEntries[] = [
                'label' => $this->getLabel().' ≥ '.$from,
                'clear_keys' => $this->allClearKeys(),
            ];
        } elseif (is_string($until) && $until !== '') {
            $indicatorEntries[] = [
                'label' => $this->getLabel().' ≤ '.$until,
                'clear_keys' => $this->allClearKeys(),
            ];
        }

        return [
            'type' => 'date_range',
            'name' => $this->name,
            'label' => $this->getLabel(),
            'from_key' => $this->fromKey(),
            'until_key' => $this->untilKey(),
            'from_state_key' => "filters.{$this->name}.from",
            'until_state_key' => "filters.{$this->name}.until",
            'from' => $from,
            'until' => $until,
            'visible_in_dropdown' => $this->visibleInDropdown,
            'active_indicators' => $this->buildIndicators($request, $indicatorEntries),
        ];
    }

    private function fromKey(): string
    {
        return 'filter_'.$this->name.'_from';
    }

    private function untilKey(): string
    {
        return 'filter_'.$this->name.'_until';
    }

    /**
     * @return array<int, string>
     */
    private function allClearKeys(): array
    {
        return [
            "filters.{$this->name}.from",
            "filters.{$this->name}.until",
            $this->fromKey(),
            $this->untilKey(),
        ];
    }
}
