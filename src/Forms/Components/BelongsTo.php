<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use MaherElGamil\Rocket\Facades\Rocket;

final class BelongsTo extends Field
{
    /** @var class-string<Model>|null */
    private ?string $relatedModel = null;

    private string $titleColumn = 'name';

    private ?string $ownerKey = null;

    private bool $searchable = false;

    /** @var array<int, string> */
    private array $searchColumns = [];

    private int $lookupLimit = 20;

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

    /**
     * @param  array<int, string>  $columns
     */
    public function searchColumns(array $columns): self
    {
        $this->searchColumns = $columns;
        $this->searchable = true;

        return $this;
    }

    public function lookupLimit(int $limit): self
    {
        $this->lookupLimit = max(1, $limit);

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

    public function isSearchable(): bool
    {
        return $this->searchable;
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
        $extra = [
            'options' => $this->searchable ? [] : $this->loadOptions(),
            'searchable' => $this->searchable,
        ];

        if ($this->searchable) {
            $extra['lookup_url'] = $this->buildLookupUrl();
        }

        return $extra;
    }

    /**
     * Run a search query against the related model.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function runLookup(?string $query, ?string $exactId = null): array
    {
        $this->assertConfigured();

        $instance = new $this->relatedModel;
        $key = $this->ownerKey ?? $instance->getKeyName();

        $builder = $instance->newQuery();

        if ($this->modifyQuery !== null) {
            /** @var Builder $builder */
            $builder = ($this->modifyQuery)($builder) ?? $builder;
        }

        if ($exactId !== null && $exactId !== '') {
            $builder->where($key, $exactId);
        } elseif ($query !== null && $query !== '') {
            $columns = $this->searchColumns !== [] ? $this->searchColumns : [$this->titleColumn];
            $builder->where(function (Builder $q) use ($columns, $query): void {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%'.$query.'%');
                }
            });
        }

        return $builder
            ->orderBy($this->titleColumn)
            ->limit($this->lookupLimit)
            ->get([$key, $this->titleColumn])
            ->map(fn (Model $model): array => [
                'value' => (string) $model->getAttribute($key),
                'label' => (string) $model->getAttribute($this->titleColumn),
            ])
            ->all();
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

    private function buildLookupUrl(): ?string
    {
        $resource = $this->getResource();

        if ($resource === null) {
            return null;
        }

        try {
            $panel = Rocket::getCurrent();
        } catch (\Throwable) {
            return null;
        }

        return $panel->url($resource::getSlug().'/lookup/'.$this->getName());
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
