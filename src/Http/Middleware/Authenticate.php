<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Panel\PanelManager;

class Authenticate extends BaseAuthenticate
{
    protected function redirectTo(Request $request): ?string
    {
        $panelId = $request->route()?->defaults['panelId'] ?? null;

        if ($panelId !== null) {
            $panels = app(PanelManager::class);

            if ($panels->has($panelId)) {
                $panel = $panels->get($panelId);

                if ($panel->isLoginEnabled()) {
                    return $panel->url('login');
                }
            }
        }

        return parent::redirectTo($request);
    }
}
