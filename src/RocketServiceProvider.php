<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket;

use Illuminate\Support\ServiceProvider;
use MaherElGamil\Rocket\Commands\MakePanelCommand;
use MaherElGamil\Rocket\Commands\MakeResourceCommand;
use MaherElGamil\Rocket\Panel\PanelManager;

final class RocketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rocket.php', 'rocket');

        $this->app->singleton(PanelManager::class, fn () => new PanelManager);
        $this->app->alias(PanelManager::class, 'rocket');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'rocket');
        $this->loadRoutesFrom(__DIR__.'/../routes/rocket.php');

        app('translator')->addJsonPath(__DIR__.'/../lang');

        $this->publishes([
            __DIR__.'/../config/rocket.php' => config_path('rocket.php'),
        ], 'rocket-config');

        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/rocketphp'),
        ], 'rocket-assets');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/rocket'),
        ], 'rocket-views');

        $this->publishes([
            __DIR__.'/../lang' => lang_path('vendor/rocket'),
        ], 'rocket-lang');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakePanelCommand::class,
                MakeResourceCommand::class,
            ]);
        }
    }
}
