<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MaherElGamil\Rocket\Panel\PanelManager;

final class LocaleController extends Controller
{
    public function __construct(private readonly PanelManager $panels) {}

    public function store(Request $request): RedirectResponse
    {
        $panelId = $request->route()?->defaults['panelId'] ?? null;

        if ($panelId === null) {
            abort(404, 'Panel not found.');
        }

        $panel = $this->panels->get($panelId);
        $locale = $request->input('locale');

        if (! in_array($locale, $panel->getAvailableLocales(), true)) {
            abort(422, 'Invalid locale.');
        }

        session(['rocket_locale_'.$panelId => $locale]);

        return back();
    }
}
