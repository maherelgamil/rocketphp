<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ResourceController extends Controller
{
    public function __construct(private readonly PanelManager $panels) {}

    public function index(Request $request, string $resource)
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        return $this->dispatchPage($request, $panel, $resourceClass, 'index');
    }

    public function create(Request $request, string $resource)
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        return $this->dispatchPage($request, $panel, $resourceClass, 'create');
    }

    public function edit(Request $request, string $resource, string|int $record)
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        return $this->dispatchPage($request, $panel, $resourceClass, 'edit');
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        $form = $resourceClass::form(Form::make($resourceClass));
        $data = $request->validate($form->getValidationRules());
        $data = $form->processSubmission($request, $data);

        $resourceClass::query()->create($data);

        return redirect()
            ->to($panel->url($resourceClass::getSlug()))
            ->with('success', $resourceClass::getLabel().' created.');
    }

    public function update(Request $request, string $resource, string|int $record): RedirectResponse
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        /** @var Model $model */
        $model = $resourceClass::query()->findOrFail($record);

        $form = $resourceClass::form(Form::make($resourceClass));
        $data = $request->validate($form->getValidationRules($model));
        $data = $form->processSubmission($request, $data, $model);

        $model->update($data);

        return redirect()
            ->to($panel->url($resourceClass::getSlug()))
            ->with('success', $resourceClass::getLabel().' updated.');
    }

    public function destroy(Request $request, string $resource, string|int $record): RedirectResponse
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        /** @var Model $model */
        $model = $resourceClass::query()->findOrFail($record);

        $model->delete();

        return redirect()
            ->to($panel->url($resourceClass::getSlug()))
            ->with('success', $resourceClass::getLabel().' deleted.');
    }

    /**
     * @return array{0: Panel, 1: class-string<\MaherElGamil\Rocket\Resources\Resource>}
     */
    private function resolve(Request $request, string $resourceSlug): array
    {
        $panelId = $request->route()->defaults['panelId'] ?? null;

        if ($panelId === null) {
            throw new NotFoundHttpException('Rocket panel not resolved for this route.');
        }

        $panel = $this->panels->get($panelId);
        $this->panels->setCurrent($panelId);

        $resource = $panel->findResourceBySlug($resourceSlug);

        if ($resource === null) {
            throw new NotFoundHttpException("Resource [{$resourceSlug}] not found in panel [{$panelId}].");
        }

        return [$panel, $resource];
    }

    /**
     * @param  class-string<\MaherElGamil\Rocket\Resources\Resource>  $resource
     */
    private function dispatchPage(Request $request, Panel $panel, string $resource, string $pageKey)
    {
        $pages = $resource::getPages();
        $pageClass = $pages[$pageKey] ?? null;

        if ($pageClass === null) {
            throw new NotFoundHttpException("Page [{$pageKey}] not defined for this resource.");
        }

        return (new $pageClass())->handle($request, $panel, $resource);
    }
}
