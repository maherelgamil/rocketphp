<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Panel;

use Illuminate\Support\ServiceProvider;

abstract class PanelProvider extends ServiceProvider
{
    /**
     * Configure the panel. Override this in your concrete provider and
     * return the fully configured Panel instance.
     */
    abstract public function panel(Panel $panel): Panel;

    public function register(): void
    {
        $panel = $this->panel(Panel::make($this->panelId()));

        $this->app->make(PanelManager::class)->register($panel);
    }

    public function boot(): void
    {
        //
    }

    /**
     * Derive the default panel id from the provider class name.
     *
     * AdminPanelProvider -> admin
     * CustomerPortalPanelProvider -> customer-portal
     */
    protected function panelId(): string
    {
        $base = class_basename(static::class);
        $base = preg_replace('/PanelProvider$/', '', $base) ?: $base;

        return strtolower(
            (string) preg_replace('/(?<!^)[A-Z]/', '-$0', $base)
        );
    }
}
