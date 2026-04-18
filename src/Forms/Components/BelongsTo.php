<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class BelongsTo extends Field
{
    /** @var class-string<Model>|null */
    private ?string $relatedModel = null;

    private string $titleColumn = 'name';

    private ?string $ownerKey = null;

    private bool $searchable = false;

    private ?Closure $modifyQuery = null;

    /**
     * @param  class-string<Model>  $model
     */
    public function related(string $model, string $titleColumn = 'name', ?string $ownerKey = null): self
    {
        $this->relatedModel = $model;
        $this->titleColumn = $titleColumn;
        $this->ownerKey = $ownerKey;

        return $this;
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function modifyQuery(Closure $callback): self
    {
        $this->modifyQuery = $callback;

        return $this;
    }

    public function type(): string
    {
        return 'select';
    }

    protected function typeRules(): array
    {
        $this->assertConfigured();

        $instance = new $this->relatedModel;
        $key = $this->ownerKey ?? $instance->getKeyName();

        return ['exists:'.$instance->getTable().','.$key];
    }

    protected function extraProps(): array
    {
        return [
            'options' => $this->loadOptions(),
            'searchable' => $this->searchable,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function loadOptions(): array
    {
        if ($this->relatedModel === null) {
            return [];
        }

        $instance = new $this->relatedModel;
        $key = $this->ownerKey ?? $instance->getKeyName();

        $query = $instance->newQuery();

        if ($this->modifyQuery !== null) {
            /** @var Builder $query */
            $query = ($this->modifyQuery)($query) ?? $query;
        }

        return $query
            ->orderBy($this->titleColumn)
            ->pluck($this->titleColumn, $key)
            ->map(static fn ($value): string => (string) $value)
            ->all();
    }

    private function assertConfigured(): void
    {
        if ($this->relatedModel === null) {
            throw new InvalidArgumentException(
                "BelongsTo field [{$this->getName()}] is missing a related model. Call ->related(Model::class)."
            );
        }
    }
}
