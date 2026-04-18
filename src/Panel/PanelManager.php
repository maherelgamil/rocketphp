<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Panel;

use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use MaherElGamil\Rocket\Http\Controllers\ResourceController;
use MaherElGamil\Rocket\Http\Middleware\HandleRocketRequests;

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
            [HandleRocketRequests::class],
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
            Route::get('{resource}', [ResourceController::class, 'index'])
                ->defaults('panelId', $panel->id())
                ->name('resource.index')
                ->where('resource', '[a-z0-9\-_]+');
        });
    }
}
