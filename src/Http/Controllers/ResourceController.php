<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MaherElGamil\Rocket\Panel\PanelManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ResourceController extends Controller
{
    public function __construct(private readonly PanelManager $panels) {}

    public function index(Request $request, string $resource)
    {
        $panelId = $request->route()->defaults['panelId'] ?? null;

        if ($panelId === null) {
            throw new NotFoundHttpException('Rocket panel not resolved for this route.');
        }

        $panel = $this->panels->get($panelId);
        $this->panels->setCurrent($panelId);

        $resourceSlug = $resource;
        $resource = $panel->findResourceBySlug($resourceSlug);

        if ($resource === null) {
            throw new NotFoundHttpException("Resource [{$resourceSlug}] not found in panel [{$panelId}].");
        }

        $pages = $resource::getPages();
        $pageClass = $pages['index'] ?? null;

        if ($pageClass === null) {
            throw new NotFoundHttpException('Index page not defined for this resource.');
        }

        return (new $pageClass())->handle($request, $panel, $resource);
    }
}
