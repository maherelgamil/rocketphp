<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Tables\Actions\Action;
use MaherElGamil\Rocket\Tables\Actions\BulkAction;
use MaherElGamil\Rocket\Tables\Actions\HeaderAction;
use MaherElGamil\Rocket\Tables\Columns\Column;
use MaherElGamil\Rocket\Tables\Filters\Filter;
use MaherElGamil\Rocket\Tables\Filters\FiltersLayout;

final class Table
{
    /** @var array<int, Column> */
    private array $columns = [];

    /** @var array<int, string> */
    private array $searchable = [];

    /** @var array<int, Filter> */
    private array $filters = [];

    /** @var array<string, Action> */
    private array $rowActions = [];

    /** @var array<string, BulkAction> */
    private array $bulkActions = [];

    /** @var array<string, HeaderAction> */
    private array $headerActions = [];

    private ?string $defaultSort = null;

    private string $defaultSortDirection = 'asc';

    private int $actionsOverflowAfter = 3;

    private ?string $paginationStyle = null;

    private FiltersLayout $filtersLayout = FiltersLayout::Dropdown;

    private int $filtersFormColumns = 1;

    private string $filtersFormWidth = 'sm';

    private bool $deferFilters = false;

    private ?string $filtersTriggerLabel = null;

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

    /**
     * @param  array<int, Filter>  $filters
     */
    public function filters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array<int, Filter>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param  array<int, Action>  $actions
     */
    public function actions(array $actions): self
    {
        foreach ($actions as $action) {
            $this->rowActions[$action->getName()] = $action;
        }

        return $this;
    }

    /**
     * @param  array<int, BulkAction>  $actions
     */
    public function bulkActions(array $actions): self
    {
        foreach ($actions as $action) {
            $this->bulkActions[$action->getName()] = $action;
        }

        return $this;
    }

    /**
     * @param  array<int, HeaderAction>  $actions
     */
    public function headerActions(array $actions): self
    {
        foreach ($actions as $action) {
            $this->headerActions[$action->getName()] = $action;
        }

        return $this;
    }

    public function getHeaderAction(string $name): ?HeaderAction
    {
        return $this->headerActions[$name] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function headerActionsToArray(): array
    {
        return array_map(static fn (HeaderAction $a) => $a->toArray(), array_values($this->headerActions));
    }

    public function actionsOverflowAfter(int $after): self
    {
        $this->actionsOverflowAfter = max(0, $after);

        return $this;
    }

    public function getActionsOverflowAfter(): int
    {
        return $this->actionsOverflowAfter;
    }

    public function paginationStyle(string $style): self
    {
        $this->paginationStyle = in_array($style, ['simple', 'numbered', 'compact'], true)
            ? $style
            : null;

        return $this;
    }

    public function filtersLayout(FiltersLayout $layout): self
    {
        $this->filtersLayout = $layout;

        return $this;
    }

    public function getFiltersLayout(): FiltersLayout
    {
        return $this->filtersLayout;
    }

    public function filtersFormColumns(int $columns): self
    {
        $this->filtersFormColumns = max(1, min(4, $columns));

        return $this;
    }

    public function filtersFormWidth(string $width): self
    {
        $this->filtersFormWidth = in_array($width, ['sm', 'md', 'lg', 'xl'], true) ? $width : 'sm';

        return $this;
    }

    public function deferFilters(bool $defer = true): self
    {
        $this->deferFilters = $defer;

        return $this;
    }

    public function filtersTriggerLabel(string $label): self
    {
        $this->filtersTriggerLabel = $label;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function filtersLayoutToArray(): array
    {
        $layout = $this->filtersLayout->value;
        $defaultDeferred = in_array($layout, ['dropdown', 'left_sidebar', 'right_sidebar'], true);

        return [
            'layout' => $layout,
            'columns' => $this->filtersFormColumns,
            'width' => $this->filtersFormWidth,
            'defer' => $this->deferFilters || $defaultDeferred,
            'trigger_label' => $this->filtersTriggerLabel,
        ];
    }

    public function getPaginationStyle(): string
    {
        if ($this->paginationStyle !== null) {
            return $this->paginationStyle;
        }

        $configured = (string) config('rocket.pagination.style', 'numbered');

        return in_array($configured, ['simple', 'numbered', 'compact'], true)
            ? $configured
            : 'numbered';
    }

    public function getRowAction(string $name): ?Action
    {
        return $this->rowActions[$name] ?? null;
    }

    public function getBulkAction(string $name): ?BulkAction
    {
        return $this->bulkActions[$name] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rowActionsToArray(): array
    {
        return array_map(static fn (Action $a) => $a->toArray(), array_values($this->rowActions));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function bulkActionsToArray(): array
    {
        return array_map(static fn (BulkAction $a) => $a->toArray(), array_values($this->bulkActions));
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        foreach ($this->filters as $filter) {
            $filter->apply($query, $request);
        }
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

    public function applyDefaultSort(Builder $query): void
    {
        if ($this->defaultSort === null) {
            return;
        }

        foreach ($this->columns as $col) {
            if ($col->getName() === $this->defaultSort && $col->isSortable()) {
                $query->orderBy($this->defaultSort, $this->defaultSortDirection);

                return;
            }
        }
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
            'pagination_style' => $this->getPaginationStyle(),
            'filters_layout' => $this->filtersLayoutToArray(),
        ];
    }
}
