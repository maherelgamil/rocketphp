<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Resources\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Tables\Table;

class ListRecords extends Page
{
    public function handle(Request $request, Panel $panel, string $resource): Response
    {
        $resource::authorizeForRequest($request, 'viewAny');

        $table = $resource::table(Table::make($resource));

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

        $perPageOptions = array_values(array_unique(array_filter(
            array_map('intval', (array) config('rocket.pagination.per_page_options', [10, 25, 50, 100])),
            fn (int $n) => $n >= (int) config('rocket.pagination.min_per_page', 1)
                && $n <= (int) config('rocket.pagination.max_per_page', 100),
        )));
        sort($perPageOptions);

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

        $tableFilters = array_map(
            static fn ($filter) => $filter->toSchema($request),
            $table->getFilters(),
        );

        return Inertia::render(static::component(), [
            'panel' => $panel->toSharedProps(),
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
            'table_filters' => $tableFilters,
            'records' => $records,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'per_page_options' => $perPageOptions,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'query' => $request->query(),
        ]);
    }

    public static function component(): string
    {
        return 'rocket/ListRecords';
    }
}
