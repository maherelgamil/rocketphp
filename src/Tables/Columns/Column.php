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
