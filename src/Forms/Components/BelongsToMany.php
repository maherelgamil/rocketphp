<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class BelongsToMany extends Field
{
    /** @var class-string<Model>|null */
    private ?string $relatedModel = null;

    private string $titleColumn = 'name';

    private ?Closure $modifyQuery = null;

    /**
     * @param  class-string<Model>  $model
     */
    public function related(string $model, string $titleColumn = 'name'): self
    {
        $this->relatedModel = $model;
        $this->titleColumn = $titleColumn;

        return $this;
    }

    public function modifyQuery(Closure $callback): self
    {
        $this->modifyQuery = $callback;

        return $this;
    }

    public function type(): string
    {
        return 'multi_select';
    }

    protected function typeRules(): array
    {
        return ['array'];
    }

    protected function extraProps(): array
    {
        return [
            'options' => $this->loadOptions(),
        ];
    }

    public function getState(Model $record): mixed
    {
        $relation = $record->{$this->name}();

        if (! $relation instanceof EloquentBelongsToMany) {
            return [];
        }

        return $relation->pluck($relation->getRelated()->getKeyName())->map(
            static fn ($v): string => (string) $v,
        )->all();
    }

    public function processSubmission(Request $request, mixed $value, ?Model $record): mixed
    {
        return Field::SKIP;
    }

    public function afterSave(Model $record, mixed $value): void
    {
        $relation = $record->{$this->name}();

        if (! $relation instanceof EloquentBelongsToMany) {
            return;
        }

        $ids = is_array($value) ? array_values(array_filter($value, static fn ($v) => $v !== null && $v !== '')) : [];

        $relation->sync($ids);
    }

    /**
     * @return array<string, string>
     */
    private function loadOptions(): array
    {
        if ($this->relatedModel === null) {
            throw new InvalidArgumentException(
                "BelongsToMany field [{$this->getName()}] is missing a related model. Call ->related(Model::class)."
            );
        }

        $instance = new $this->relatedModel;
        $query = $instance->newQuery();

        if ($this->modifyQuery !== null) {
            $query = ($this->modifyQuery)($query) ?? $query;
        }

        return $query
            ->orderBy($this->titleColumn)
            ->pluck($this->titleColumn, $instance->getKeyName())
            ->map(static fn ($v): string => (string) $v)
            ->all();
    }
}
