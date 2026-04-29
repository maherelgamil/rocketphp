<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Columns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class Column
{
    protected ?string $label = null;

    protected bool $sortable = false;

    protected bool $searchable = false;

    protected bool $toggleable = false;

    protected bool $toggledHiddenByDefault = false;

    protected ?Closure $formatStateUsing = null;

    final public function __construct(protected readonly string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? (string) Str::of($this->name)->replace('_', ' ')->title();
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function toggleable(bool $toggleable = true, bool $isToggledHiddenByDefault = false): static
    {
        $this->toggleable = $toggleable;
        $this->toggledHiddenByDefault = $isToggledHiddenByDefault;

        return $this;
    }

    public function toggledHiddenByDefault(bool $hidden = true): static
    {
        $this->toggleable = true;
        $this->toggledHiddenByDefault = $hidden;

        return $this;
    }

    public function isToggleable(): bool
    {
        return $this->toggleable;
    }

    public function isToggledHiddenByDefault(): bool
    {
        return $this->toggledHiddenByDefault;
    }

    public function formatStateUsing(Closure $callback): static
    {
        $this->formatStateUsing = $callback;

        return $this;
    }

    public function getState(Model $record): mixed
    {
        return data_get($record, $this->name);
    }

    public function render(Model $record): mixed
    {
        $state = $this->getState($record);

        if ($this->formatStateUsing !== null) {
            $state = ($this->formatStateUsing)($state, $record);
        }

        return $state;
    }

    abstract public function type(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'name' => $this->name,
            'label' => $this->getLabel(),
            'sortable' => $this->sortable,
            'toggleable' => $this->toggleable,
            'toggled_hidden_by_default' => $this->toggledHiddenByDefault,
            'extra' => $this->extraProps(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraProps(): array
    {
        return [];
    }
}
