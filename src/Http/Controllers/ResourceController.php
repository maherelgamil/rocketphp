<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MaherElGamil\Rocket\Forms\Components\BelongsTo;
use MaherElGamil\Rocket\Forms\Components\Field;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tables\Table;
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

    public function view(Request $request, string $resource, string|int $record)
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        return $this->dispatchPage($request, $panel, $resourceClass, 'view');
    }

    public function lookup(Request $request, string $resource, string $field): JsonResponse
    {
        [, $resourceClass] = $this->resolve($request, $resource);

        $ability = $request->user() && $request->filled('record') ? 'update' : 'create';
        $resourceClass::authorizeForRequest($request, $ability);

        $form = $resourceClass::form(Form::make($resourceClass));
        $target = $this->findField($form, $field);

        if (! $target instanceof BelongsTo || ! $target->isSearchable()) {
            abort(404);
        }

        $target->setResource($resourceClass);

        $results = $target->runLookup(
            $request->string('q')->toString() ?: null,
            $request->string('id')->toString() ?: null,
        );

        return response()->json(['results' => $results]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);
        $resourceClass::authorizeForRequest($request, 'create');

        $form = $resourceClass::form(Form::make($resourceClass));
        $validated = $request->validate($form->getValidationRules());
        $data = $form->processSubmission($request, $validated);

        $model = $resourceClass::query()->create($data);
        $form->applyAfterSave($model, $validated);

        return redirect()
            ->to($panel->url($resourceClass::getSlug()))
            ->with('success', $resourceClass::getLabel().' created.');
    }

    public function update(Request $request, string $resource, string|int $record): RedirectResponse
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        /** @var Model $model */
        $model = $resourceClass::query()->findOrFail($record);
        $resourceClass::authorizeForRequest($request, 'update', $model);

        $form = $resourceClass::form(Form::make($resourceClass));
        $validated = $request->validate($form->getValidationRules($model));
        $data = $form->processSubmission($request, $validated, $model);

        $model->update($data);
        $form->applyAfterSave($model, $validated);

        return redirect()
            ->to($panel->url($resourceClass::getSlug()))
            ->with('success', $resourceClass::getLabel().' updated.');
    }

    public function destroy(Request $request, string $resource, string|int $record): RedirectResponse
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        /** @var Model $model */
        $model = $resourceClass::query()->findOrFail($record);
        $resourceClass::authorizeForRequest($request, 'delete', $model);

        $model->delete();

        return redirect()
            ->to($panel->url($resourceClass::getSlug()))
            ->with('success', $resourceClass::getLabel().' deleted.');
    }

    public function rowAction(Request $request, string $resource, string|int $record, string $action): RedirectResponse
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        $table = $resourceClass::table(Table::make($resourceClass));
        $rowAction = $table->getRowAction($action);
        if ($rowAction === null) {
            abort(404);
        }

        /** @var Model $model */
        $model = $resourceClass::query()->findOrFail($record);
        $rowAction->authorize($request, $resourceClass, $model);
        $rowAction->handle($model);

        $message = match ($action) {
            'delete' => $resourceClass::getLabel().' deleted.',
            default => 'Action completed.',
        };

        return redirect()
            ->to($panel->url($resourceClass::getSlug()))
            ->with('success', $message);
    }

    public function bulkAction(Request $request, string $resource, string $bulkAction): RedirectResponse
    {
        [$panel, $resourceClass] = $this->resolve($request, $resource);

        $table = $resourceClass::table(Table::make($resourceClass));
        $bulk = $table->getBulkAction($bulkAction);
        if ($bulk === null) {
            abort(404);
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required',
        ]);

        /** @var Model $instance */
        $instance = $resourceClass::getModel()::query()->newModelInstance();
        $keyName = $instance->getKeyName();

        $models = $resourceClass::query()->whereIn($keyName, $validated['ids'])->get();

        foreach ($models as $model) {
            $bulk->authorizeRecord($request, $resourceClass, $model);
        }

        $bulk->handle($models);

        return redirect()
            ->to($panel->url($resourceClass::getSlug()))
            ->with('success', $resourceClass::getPluralLabel().' deleted.');
    }

    private function findField(Form $form, string $name): ?Field
    {
        foreach ($form->getFields() as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }

        return null;
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

        return (new $pageClass)->handle($request, $panel, $resource);
    }
}
