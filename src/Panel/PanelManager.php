<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Panel;

use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use MaherElGamil\Rocket\Http\Controllers\DashboardController;
use MaherElGamil\Rocket\Http\Controllers\GlobalSearchController;
use MaherElGamil\Rocket\Http\Controllers\ImportController;
use MaherElGamil\Rocket\Http\Controllers\LocaleController;
use MaherElGamil\Rocket\Http\Controllers\NotificationController;
use MaherElGamil\Rocket\Http\Controllers\PageController;
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

            Route::get('/', [DashboardController::class, 'show'])
                ->defaults('panelId', $panel->id())
                ->name('dashboard');

            if ($panel->isGlobalSearchEnabled()) {
                Route::get('search', [GlobalSearchController::class, 'search'])
                    ->defaults('panelId', $panel->id())
                    ->name('search');
            }

            if ($panel->isNotificationsEnabled()) {
                Route::get('notifications', [NotificationController::class, 'index'])
                    ->defaults('panelId', $panel->id())
                    ->name('notifications.index');

                Route::get('notifications/recent', [NotificationController::class, 'recent'])
                    ->defaults('panelId', $panel->id())
                    ->name('notifications.recent');

                Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
                    ->defaults('panelId', $panel->id())
                    ->name('notifications.read-all');

                Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
                    ->defaults('panelId', $panel->id())
                    ->name('notifications.read');
            }

            Route::post('locale', [LocaleController::class, 'store'])
                ->defaults('panelId', $panel->id())
                ->name('locale.store');

            Route::get('pages/{page}', [PageController::class, 'show'])
                ->defaults('panelId', $panel->id())
                ->name('pages.show')
                ->where('page', '[a-z0-9\-_]+');

            Route::post('pages/{page}/actions/{action}', [PageController::class, 'action'])
                ->defaults('panelId', $panel->id())
                ->name('pages.action')
                ->where('page', '[a-z0-9\-_]+')
                ->where('action', '[a-z0-9\-_]+');

            Route::post('{resource}/header-actions/{headerAction}', [ResourceController::class, 'headerAction'])
                ->defaults('panelId', $panel->id())
                ->name('resource.header-action')
                ->where('resource', $resourceConstraint)
                ->where('headerAction', '[a-z0-9\-_]+');

            Route::get('exports/{export}/download', [ResourceController::class, 'downloadExport'])
                ->defaults('panelId', $panel->id())
                ->name('exports.download')
                ->where('export', '[0-9]+');

            Route::get('imports/{import}', [ImportController::class, 'show'])
                ->defaults('panelId', $panel->id())
                ->name('imports.show')
                ->where('import', '[0-9]+');

            Route::get('imports/{import}/status', [ImportController::class, 'status'])
                ->defaults('panelId', $panel->id())
                ->name('imports.status')
                ->where('import', '[0-9]+');

            Route::get('imports/{import}/failed-rows.csv', [ImportController::class, 'downloadFailedRows'])
                ->defaults('panelId', $panel->id())
                ->name('imports.failed-rows')
                ->where('import', '[0-9]+');

            Route::get('importers/{importer}/example.csv', [ImportController::class, 'example'])
                ->defaults('panelId', $panel->id())
                ->name('importers.example')
                ->where('importer', '[A-Za-z0-9=+\/]+');

            Route::post('importers/{importer}/preview', [ImportController::class, 'preview'])
                ->defaults('panelId', $panel->id())
                ->name('importers.preview')
                ->where('importer', '[A-Za-z0-9=+\/]+');

            Route::post('{resource}/bulk-actions/{bulkAction}', [ResourceController::class, 'bulkAction'])
                ->defaults('panelId', $panel->id())
                ->name('resource.bulk-action')
                ->where('resource', $resourceConstraint)
                ->where('bulkAction', '[a-z0-9\-_]+');

            // Row action and custom-page action share the same shape
            // (/{resource}/{x}/actions/{action}); a single dispatcher
            // decides whether {x} is a record id or a custom page slug.
            Route::post('{resource}/{recordOrPage}/actions/{action}', [ResourceController::class, 'recordOrPageAction'])
                ->defaults('panelId', $panel->id())
                ->name('resource.row-action')
                ->where(['resource' => $resourceConstraint, 'recordOrPage' => $recordConstraint])
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

            Route::get('{resource}/{pageSlug}', [ResourceController::class, 'customPage'])
                ->defaults('panelId', $panel->id())
                ->name('resources.custom-page')
                ->where([
                    'resource' => $resourceConstraint,
                    'pageSlug' => '[a-z0-9\-_]+',
                ]);

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
