<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MaherElGamil\Rocket\Panel\PanelManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GlobalSearchController extends Controller
{
    public function __construct(private readonly PanelManager $panels) {}

    public function search(Request $request): JsonResponse
    {
        $panelId = $request->route()?->defaults['panelId'] ?? null;

        if ($panelId === null) {
            throw new NotFoundHttpException('Rocket panel not resolved for this route.');
        }

        $panel = $this->panels->get($panelId);
        $this->panels->setCurrent($panelId);

        if (! $panel->isGlobalSearchEnabled()) {
            throw new NotFoundHttpException('Global search is not enabled for this panel.');
        }

        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $results = [];
        $total = 0;

        foreach ($panel->getResources() as $resource) {
            if (! $resource::can($request, 'viewAny')) {
                continue;
            }

            $columns = $resource::globalSearchColumns();

            if ($columns === []) {
                continue;
            }

            $builder = $resource::getGlobalSearchEloquentQuery();
            $builder->where(function ($q) use ($columns, $query): void {
                foreach ($columns as $i => $column) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->{$method}($column, 'like', '%'.$query.'%');
                }
            });

            $records = $builder->take(5)->get();

            if ($records->isEmpty()) {
                continue;
            }

            $pages = $resource::getPages();
            $resourceItems = [];

            foreach ($records as $record) {
                $result = $resource::globalSearchResult($record);

                if (array_key_exists('edit', $pages)) {
                    $result['url'] = $panel->url($resource::getSlug().'/'.$record->getKey().'/edit');
                } elseif (array_key_exists('view', $pages)) {
                    $result['url'] = $panel->url($resource::getSlug().'/'.$record->getKey().'/view');
                } else {
                    $result['url'] = $panel->url($resource::getSlug());
                }

                $resourceItems[] = $result;
                $total++;

                if ($total >= 50) {
                    break;
                }
            }

            $results[] = [
                'resource' => [
                    'label' => $resource::getPluralLabel(),
                    'icon' => $resource::getNavigationIcon(),
                ],
                'items' => $resourceItems,
            ];

            if ($total >= 50) {
                break;
            }
        }

        return response()->json(['results' => $results]);
    }
}
