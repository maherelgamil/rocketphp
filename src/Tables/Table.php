<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MaherElGamil\Rocket\Tables\Columns\Column;

final class Table
{
    /** @var array<int, Column> */
    private array $columns = [];

    /** @var array<int, string> */
    private array $searchable = [];

    private ?string $defaultSort = null;

    private string $defaultSortDirection = 'asc';

    /**
     * @param  class-string<\MaherElGamil\Rocket\Resources\Resource>  $resource
     */
    public function __construct(private readonly string $resource) {}

    /**
     * @param  class-string<\MaherElGamil\Rocket\Resources\Resource>  $resource
     */
    public static function make(string $resource): self
    {
        return new self($resource);
    }

    /**
     * @param  array<int, Column>  $columns
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return array<int, Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param  array<int, string>  $columns
     */
    public function searchable(array $columns): self
    {
        $this->searchable = $columns;

        return $this;
    }

    public function defaultSort(string $column, string $direction = 'asc'): self
    {
        $this->defaultSort = $column;
        $this->defaultSortDirection = $direction === 'desc' ? 'desc' : 'asc';

        return $this;
    }

    public function applySearch(Builder $query, string $term): void
    {
        if ($this->searchable === []) {
            return;
        }

        $query->where(function (Builder $inner) use ($term): void {
            foreach ($this->searchable as $column) {
                $inner->orWhere($column, 'like', "%{$term}%");
            }
        });
    }

    public function applySort(Builder $query, string $column, string $direction): void
    {
        foreach ($this->columns as $col) {
            if ($col->getName() === $column && $col->isSortable()) {
                $query->orderBy($column, $direction);

                return;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function renderRow(Model $record): array
    {
        $row = ['_key' => $record->getKey()];

        foreach ($this->columns as $column) {
            $row[$column->getName()] = $column->render($record);
        }

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'columns' => array_map(static fn (Column $c) => $c->toArray(), $this->columns),
            'searchable' => $this->searchable !== [],
            'default_sort' => $this->defaultSort,
            'default_sort_direction' => $this->defaultSortDirection,
        ];
    }
}
