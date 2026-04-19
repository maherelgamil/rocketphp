<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Response;
use MaherElGamil\Rocket\Http\Controllers\Concerns\DispatchesPages;
use MaherElGamil\Rocket\Panel\PanelManager;

final class PageController extends Controller
{
    use DispatchesPages;

    public function __construct(private readonly PanelManager $panels) {}

    protected function panels(): PanelManager
    {
        return $this->panels;
    }

    public function show(Request $request, string $pageSlug): Response
    {
        $panel = $this->resolvePanel($request);

        $pageClass = collect($panel->getPages())
            ->first(fn ($p) => (new $p)->getSlug() === $pageSlug);

        abort_if($pageClass === null, 404);

        return $this->dispatchToPage($request, $panel, $pageClass);
    }

    public function action(Request $request, string $pageSlug, string $actionName): mixed
    {
        $panel = $this->resolvePanel($request);

        $pageClass = collect($panel->getPages())
            ->first(fn ($p) => (new $p)->getSlug() === $pageSlug);

        abort_if($pageClass === null, 404);

        $page = new $pageClass;
        $page->mount($request);

        abort_unless($page->can($request), 403);

        $action = collect($page->actions())
            ->first(fn ($a) => $a->getName() === $actionName);

        abort_if($action === null, 404);

        $callback = $action->getCallback();

        if ($callback === null) {
            return redirect()->back();
        }

        $result = $callback($request);

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        return redirect()->back();
    }
}
