<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Tables\Table;

class ListRecordsPage extends ResourcePage
{
    protected static function getAuthAbility(): string
    {
        return 'viewAny';
    }

    public function handle(Request $request, Panel $panel): Response
    {
        $resource = $this->getResource();
        $resource::authorizeForRequest($request, 'viewAny');

        $table = $resource::table(Table::make($resource));

        $widgets = array_map(
            fn ($widget) => $widget->toArray(),
            $resource::getWidgets('list'),
        );

        $search = (string) $request->query('search', '');
        $sort = (string) $request->query('sort', '');
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = max(
            (int) config('rocket.pagination.min_per_page', 1),
            min(
                (int) config('rocket.pagination.max_per_page', 100),
                (int) $request->query('per_page', (int) config('rocket.pagination.per_page', 25)),
            ),
        );

        $query = $resource::query();
        $table->applyFilters($query, $request);

        if ($search !== '') {
            $table->applySearch($query, $search);
        }

        if ($sort !== '') {
            $table->applySort($query, $sort, $direction);
        } else {
            $table->applyDefaultSort($query);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $records = $paginator->getCollection()
            ->map(function ($record) use ($table, $resource, $request) {
                $row = $table->renderRow($record);
                $row['_can_update'] = $resource::hasForm()
                    && $resource::can($request, 'update', $record);
                $row['_can_delete'] = $resource::can($request, 'delete', $record);
                $row['_can_view'] = $resource::can($request, 'view', $record);

                return $row;
            })
            ->all();

        return Inertia::render($this->component(), [
            'panel' => $panel->toSharedProps(),
            'page' => [
                'title' => $this->getTitle(),
                'subtitle' => $this->getSubtitle(),
                'slug' => $this->getSlug(),
            ],
            'resource' => [
                'slug' => $resource::getSlug(),
                'label' => $resource::getLabel(),
                'pluralLabel' => $resource::getPluralLabel(),
                'hasForm' => $resource::hasForm(),
                'can' => [
                    'create' => $resource::can($request, 'create'),
                ],
            ],
            'table' => $table->toArray(),
            'row_actions' => $table->rowActionsToArray(),
            'row_actions_overflow_after' => $table->getActionsOverflowAfter(),
            'bulk_actions' => $table->bulkActionsToArray(),
            'header_actions' => $table->headerActionsToArray(),
            'table_filters' => array_map(
                static fn ($filter) => $filter->toSchema($request),
                $table->getFilters(),
            ),
            'records' => $records,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'per_page_options' => array_values(array_unique(array_filter(
                array_map('intval', (array) config('rocket.pagination.per_page_options', [10, 25, 50, 100])),
                fn (int $n) => $n >= (int) config('rocket.pagination.min_per_page', 1)
                    && $n <= (int) config('rocket.pagination.max_per_page', 100),
            ))),
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'query' => $request->query(),
            'widgets' => $widgets,
        ]);
    }

    public function component(): string
    {
        return 'rocket/list-records';
    }

    public function can(Request $request): bool
    {
        $resource = $this->getResource();

        if ($resource === null) {
            return parent::can($request);
        }

        return $resource::can($request, 'viewAny');
    }
}
