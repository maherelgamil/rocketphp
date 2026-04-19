<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Resources\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use MaherElGamil\Rocket\Tables\Table;

final class RelationManagerRenderer
{
    /**
     * @param  array<int, class-string<RelationManager>>  $managers
     * @return array<string, array<string, mixed>>
     */
    public static function render(array $managers, Model $ownerRecord, Request $request): array
    {
        $out = [];

        foreach ($managers as $managerClass) {
            $user = $request->user();
            if ($user === null) {
                continue;
            }

            if (! Gate::forUser($user)->check('viewAny', $managerClass::getRelatedModel())) {
                continue;
            }

            $name = $managerClass::getName();
            $prefix = 'rm_'.$name.'_';
            $scopedRequest = self::scopeRequest($request, $prefix);

            $table = $managerClass::table(Table::make($managerClass));

            $search = (string) $scopedRequest->query('search', '');
            $sort = (string) $scopedRequest->query('sort', '');
            $direction = $scopedRequest->query('direction') === 'desc' ? 'desc' : 'asc';
            $defaultPerPage = (int) config(
                'rocket.pagination.relation_manager.per_page',
                (int) config('rocket.pagination.per_page', 25),
            );
            $perPage = max(
                (int) config('rocket.pagination.min_per_page', 1),
                min(
                    (int) config('rocket.pagination.max_per_page', 100),
                    (int) $scopedRequest->query('per_page', $defaultPerPage),
                ),
            );

            $perPageOptions = array_values(array_unique(array_filter(
                array_map('intval', (array) config('rocket.pagination.per_page_options', [10, 25, 50, 100])),
                fn (int $n) => $n >= (int) config('rocket.pagination.min_per_page', 1)
                    && $n <= (int) config('rocket.pagination.max_per_page', 100),
            )));
            sort($perPageOptions);

            $query = $managerClass::query($ownerRecord);

            $table->applyFilters($query, $scopedRequest);

            if ($search !== '') {
                $table->applySearch($query, $search);
            }

            if ($sort !== '') {
                $table->applySort($query, $sort, $direction);
            } else {
                $table->applyDefaultSort($query);
            }

            $pageName = $prefix.'page';
            $paginator = $query->paginate($perPage, ['*'], $pageName, (int) $scopedRequest->query('page', 1))
                ->withQueryString();

            $records = $paginator->getCollection()
                ->map(fn ($record) => $table->renderRow($record))
                ->all();

            $tableFilters = array_map(
                static fn ($filter) => self::rekeyFilterSchema($filter->toSchema($scopedRequest), $prefix),
                $table->getFilters(),
            );

            $out[$name] = [
                'name' => $name,
                'title' => $managerClass::getTitle(),
                'prefix' => $prefix,
                'page_key' => $pageName,
                'table' => $table->toArray(),
                'row_actions' => $table->rowActionsToArray(),
                'row_actions_overflow_after' => $table->getActionsOverflowAfter(),
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
            ];
        }

        return $out;
    }

    private static function scopeRequest(Request $request, string $prefix): Request
    {
        $scoped = [];
        foreach ($request->query() as $key => $value) {
            if (is_string($key) && str_starts_with($key, $prefix)) {
                $scoped[substr($key, strlen($prefix))] = $value;
            }
        }

        $new = Request::create($request->fullUrl(), $request->method(), $scoped);
        $new->setUserResolver($request->getUserResolver());

        return $new;
    }

    /**
     * Filter schemas expose their query keys (e.g. `filter_status`) so the
     * frontend knows what URL param to mutate. Re-prefix them so the manager's
     * controls hit the scoped query params.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private static function rekeyFilterSchema(array $schema, string $prefix): array
    {
        foreach (['query_key', 'from_key', 'until_key'] as $key) {
            if (isset($schema[$key]) && is_string($schema[$key])) {
                $schema[$key] = $prefix.$schema[$key];
            }
        }

        return $schema;
    }
}
