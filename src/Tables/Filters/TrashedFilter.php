<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * For models using {@see SoftDeletes}: without (default), with, only.
 */
final class TrashedFilter extends Filter
{
    public function __construct(string $name = 'trashed')
    {
        parent::__construct($name);
        $this->default = 'without';
    }

    public function apply(Builder $query, Request $request): void
    {
        $model = $query->getModel();
        if (! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            return;
        }

        $value = (string) $this->readState($request);

        $this->persistState($request, ['__value' => $value]);

        match ($value) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array
    {
        $value = $this->readState($request);
        $stringValue = is_string($value) && $value !== '' ? $value : 'without';

        $options = [
            'without' => 'Without trashed',
            'with' => 'With trashed',
            'only' => 'Only trashed',
        ];

        $indicators = [];
        if ($stringValue !== 'without') {
            $indicators = $this->buildIndicators($request, [[
                'label' => $options[$stringValue] ?? $stringValue,
                'clear_keys' => ["filters.{$this->name}", $this->legacyKey()],
            ]]);
        }

        return [
            'type' => 'trashed',
            'name' => $this->name,
            'query_key' => $this->legacyKey(),
            'state_key' => "filters.{$this->name}",
            'label' => $this->label ?? 'Trashed',
            'options' => $options,
            'value' => $stringValue,
            'visible_in_dropdown' => $this->visibleInDropdown,
            'active_indicators' => $indicators,
        ];
    }

    private function legacyKey(): string
    {
        return 'filter_'.$this->name;
    }
}
