<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Panel;

use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use MaherElGamil\Rocket\Http\Controllers\DashboardController;
use MaherElGamil\Rocket\Http\Controllers\GlobalSearchController;
use MaherElGamil\Rocket\Http\Controllers\ResourceController;
use MaherElGamil\Rocket\Http\Middleware\HandleRocketRequests;
use MaherElGamil\Rocket\Http\Middleware\RenderRocketErrorPages;

final class PanelManager
{
    /** @var array<string, Panel> */
    private array $panels = [];

    private ?string $current = null;

    public function panel(string $id): Panel
    {
        return $this->panels[$id] ??= Panel::make($id);
    }

    public function register(Panel $panel): Panel
    {
        $this->panels[$panel->id()] = $panel;
        $this->registerRoutes($panel);

        return $panel;
    }

    public function get(string $id): Panel
    {
        if (! isset($this->panels[$id])) {
            throw new InvalidArgumentException("Rocket panel [{$id}] is not registered.");
        }

        return $this->panels[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->panels[$id]);
    }

    public function setCurrent(string $id): void
    {
        $this->current = $id;
    }

    public function getCurrent(): Panel
    {
        $id = $this->current ?? config('rocket.default_panel', 'admin');

        return $this->get($id);
    }

    /**
     * @return array<string, Panel>
     */
    public function all(): array
    {
        return $this->panels;
    }

    private function registerRoutes(Panel $panel): void
    {
        $middleware = array_values(array_filter(array_merge(
            $panel->getMiddleware(),
            $panel->getAuthMiddleware(),
            [HandleRocketRequests::class, RenderRocketErrorPages::class],
        )));

        $attributes = [
            'prefix' => $panel->getPath(),
            'as' => "rocket.{$panel->id()}.",
            'middleware' => $middleware,
        ];

        if ($panel->getDomain() !== null) {
            $attributes['domain'] = $panel->getDomain();
        }

        Route::group($attributes, function () use ($panel): void {
            $resourceConstraint = '[a-z0-9\-_]+';
            $recordConstraint = '[A-Za-z0-9\-_]+';
            $defaults = ['panelId' => $panel->id()];

            Route::get('dashboard', [DashboardController::class, 'show'])
                ->defaults('panelId', $panel->id())
                ->name('dashboard');

            if ($panel->isGlobalSearchEnabled()) {
                Route::get('search', [GlobalSearchController::class, 'search'])
                    ->defaults('panelId', $panel->id())
                    ->name('search');
            }

            Route::post('{resource}/bulk-actions/{bulkAction}', [ResourceController::class, 'bulkAction'])
                ->defaults('panelId', $panel->id())
                ->name('resource.bulk-action')
                ->where('resource', $resourceConstraint)
                ->where('bulkAction', '[a-z0-9\-_]+');

            Route::post('{resource}/{record}/actions/{action}', [ResourceController::class, 'rowAction'])
                ->defaults('panelId', $panel->id())
                ->name('resource.row-action')
                ->where(['resource' => $resourceConstraint, 'record' => $recordConstraint])
                ->where('action', '[a-z0-9\-_]+');

            Route::get('{resource}', [ResourceController::class, 'index'])
                ->defaults('panelId', $panel->id())
                ->name('resource.index')
                ->where('resource', $resourceConstraint);

            Route::get('{resource}/create', [ResourceController::class, 'create'])
                ->defaults('panelId', $panel->id())
                ->name('resource.create')
                ->where('resource', $resourceConstraint);

            Route::post('{resource}', [ResourceController::class, 'store'])
                ->defaults('panelId', $panel->id())
                ->name('resource.store')
                ->where('resource', $resourceConstraint);

            Route::get('{resource}/lookup/{field}', [ResourceController::class, 'lookup'])
                ->defaults('panelId', $panel->id())
                ->name('resource.lookup')
                ->where(['resource' => $resourceConstraint, 'field' => '[a-zA-Z0-9\._\-]+']);

            Route::get('{resource}/{record}/edit', [ResourceController::class, 'edit'])
                ->defaults('panelId', $panel->id())
                ->name('resource.edit')
                ->where(['resource' => $resourceConstraint, 'record' => $recordConstraint]);

            Route::get('{resource}/{record}/view', [ResourceController::class, 'view'])
                ->defaults('panelId', $panel->id())
                ->name('resource.view')
                ->where(['resource' => $resourceConstraint, 'record' => $recordConstraint]);

            Route::match(['put', 'patch'], '{resource}/{record}', [ResourceController::class, 'update'])
                ->defaults('panelId', $panel->id())
                ->name('resource.update')
                ->where(['resource' => $resourceConstraint, 'record' => $recordConstraint]);

            Route::delete('{resource}/{record}', [ResourceController::class, 'destroy'])
                ->defaults('panelId', $panel->id())
                ->name('resource.destroy')
                ->where(['resource' => $resourceConstraint, 'record' => $recordConstraint]);

            unset($defaults);
        });
    }
}
