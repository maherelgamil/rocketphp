<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Support\EnumSupport;

final class SelectFilter extends Filter
{
    private string $attribute;

    /** @var array<string, string> */
    private array $options;

    private bool $multiple = false;

    /**
     * @param  array<string, string>  $options
     */
    public function __construct(
        string $name,
        ?string $attribute = null,
        ?string $label = null,
        array $options = [],
    ) {
        parent::__construct($name);
        $this->attribute = $attribute ?? $name;
        if ($label !== null) {
            $this->label = $label;
        }
        $this->options = $options;
    }

    public function attribute(string $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @param  array<string, string>  $options
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  class-string  $enumClass
     */
    public function enum(string $enumClass): self
    {
        $this->options = EnumSupport::toOptions($enumClass);

        return $this;
    }

    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function apply(Builder $query, Request $request): void
    {
        $value = $this->readState($request);

        if ($this->multiple) {
            $values = $this->normalizeValues($value);

            if ($values === []) {
                $this->persistState($request, ['__value' => null]);

                return;
            }

            $this->persistState($request, ['__value' => $values]);
            $query->whereIn($this->attribute, $values);

            return;
        }

        if ($value === null || $value === '') {
            $this->persistState($request, ['__value' => null]);

            return;
        }

        $this->persistState($request, ['__value' => $value]);

        $query->where($this->attribute, $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array
    {
        $value = $this->readState($request);
        $indicators = [];

        if ($this->multiple) {
            $values = $this->normalizeValues($value);
            if ($values !== []) {
                $labels = array_map(
                    fn ($v) => $this->options[(string) $v] ?? (string) $v,
                    $values,
                );
                $indicators = $this->buildIndicators($request, [[
                    'label' => $this->getLabel().': '.implode(', ', $labels),
                    'clear_keys' => ["filters.{$this->name}", $this->legacyKey()],
                ]]);
            }

            return [
                'type' => 'select',
                'name' => $this->name,
                'query_key' => $this->legacyKey(),
                'state_key' => "filters.{$this->name}",
                'label' => $this->getLabel(),
                'options' => $this->options,
                'value' => $values,
                'multiple' => true,
                'visible_in_dropdown' => $this->visibleInDropdown,
                'active_indicators' => $indicators,
            ];
        }

        if ($value !== null && $value !== '') {
            $label = $this->options[(string) $value] ?? (string) $value;
            $indicators = $this->buildIndicators($request, [[
                'label' => $this->getLabel().': '.$label,
                'clear_keys' => ["filters.{$this->name}", $this->legacyKey()],
            ]]);
        }

        return [
            'type' => 'select',
            'name' => $this->name,
            'query_key' => $this->legacyKey(),
            'state_key' => "filters.{$this->name}",
            'label' => $this->getLabel(),
            'options' => $this->options,
            'value' => $value,
            'multiple' => false,
            'visible_in_dropdown' => $this->visibleInDropdown,
            'active_indicators' => $indicators,
        ];
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    private function normalizeValues($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        $raw = is_array($value) ? $value : [$value];
        $clean = [];
        foreach ($raw as $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $clean[] = (string) $v;
        }

        return array_values(array_unique($clean));
    }

    private function legacyKey(): string
    {
        return 'filter_'.$this->name;
    }
}
