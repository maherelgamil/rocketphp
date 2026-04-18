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

        $query = $resource::query();

        if ($search !== '') {
            $table->applySearch($query, $search);
        }

        if ($sort !== '') {
            $table->applySort($query, $sort, $direction);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $records = $paginator->getCollection()
            ->map(fn ($record) => $table->renderRow($record))
            ->all();

        return Inertia::render(static::component(), [
            'panel' => $panel->toSharedProps(),
            'resource' => [
                'slug' => $resource::getSlug(),
                'label' => $resource::getLabel(),
                'pluralLabel' => $resource::getPluralLabel(),
                'hasForm' => $resource::hasForm(),
            ],
            'table' => $table->toArray(),
            'records' => $records,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public static function component(): string
    {
        return 'rocket/ListRecords';
    }
}
