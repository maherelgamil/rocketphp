<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Pages;

use MaherElGamil\Rocket\Pages\Blocks\WidgetBlock;
use MaherElGamil\Rocket\Panel\Panel;

final class DashboardPage extends Page
{
    public function getSlug(): string
    {
        return 'dashboard';
    }

    public function getTitle(): string
    {
        return 'Dashboard';
    }

    public function getNavigationIcon(): ?string
    {
        return 'layout-dashboard';
    }

    public function component(): string
    {
        return 'rocket/Dashboard';
    }

    /**
     * @return array<int, WidgetBlock>
     */
    public function content(Panel $panel): array
    {
        return array_map(
            fn (mixed $widget) => new WidgetBlock($widget),
            $panel->getWidgets(),
        );
    }
}
