<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Resources\RelationManagers\RelationManagerRenderer;

class ViewRecordPage extends ResourcePage
{
    protected static function getAuthAbility(): string
    {
        return 'view';
    }

    public function handle(Request $request, Panel $panel): Response
    {
        $resource = static::getResource();
        $record = $this->getRecord() ?? $resource::query()->findOrFail($request->route('record'));
        $resource::authorizeForRequest($request, 'view', $record);

        $widgets = array_map(
            fn ($widget) => $widget->toArray(),
            $resource::getWidgets('view'),
        );

        $form = $resource::form(Form::make($resource));
        $indexUrl = $panel->url($resource::getSlug());
        $editUrl = $resource::can($request, 'update', $record)
            ? $panel->url($resource::getSlug().'/'.$record->getKey().'/edit')
            : null;

        return Inertia::render($this->component(), [
            'panel' => $panel->toSharedProps(),
            'page' => [
                'title' => $this->getTitle(),
                'subtitle' => $this->getSubtitle(),
                'slug' => $this->getSlug(),
            ],
            'resource' => [
                'slug' => $resource::getSlug(),
                'label' => $resource::getLabel(),
                'pluralLabel' => $resource::getPluralLabel(),
                'can' => [
                    'update' => $resource::can($request, 'update', $record),
                    'delete' => $resource::can($request, 'delete', $record),
                ],
            ],
            'form' => $form->toArray($record),
            'record' => ['key' => $record->getKey()],
            'state' => $form->extractState($record),
            'edit_url' => $editUrl,
            'index_url' => $indexUrl,
            'relation_managers' => RelationManagerRenderer::render($resource::relationManagers(), $record, $request),
            'relation_managers_layout' => $resource::relationManagersLayout(),
            'query' => $request->query(),
            'widgets' => $widgets,
        ]);
    }

    public function component(): string
    {
        return 'rocket/ViewRecord';
    }

    public function can(Request $request): bool
    {
        $resource = $this->getResource();

        if ($resource === null) {
            return parent::can($request);
        }

        $recordId = $request->route('record');

        if ($recordId === null) {
            return $resource::can($request, 'viewAny');
        }

        $record = $resource::query()->find($recordId);

        return $record && $resource::can($request, 'view', $record);
    }
}
