<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Resources\Pages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Resources\RelationManagers\RelationManagerRenderer;

class ViewRecord extends Page
{
    /**
     * @param  class-string<\MaherElGamil\Rocket\Resources\Resource>  $resource
     */
    public function handle(Request $request, Panel $panel, string $resource): Response
    {
        /** @var Model $record */
        $record = $resource::query()->findOrFail($request->route('record'));

        $resource::authorizeForRequest($request, 'view', $record);

        $form = $resource::form(Form::make($resource));

        return Inertia::render(static::component(), [
            'panel' => $panel->toSharedProps(),
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
            'edit_url' => $resource::can($request, 'update', $record)
                ? $panel->url($resource::getSlug().'/'.$record->getKey().'/edit')
                : null,
            'index_url' => $panel->url($resource::getSlug()),
            'relation_managers' => RelationManagerRenderer::render($resource::relationManagers(), $record, $request),
            'relation_managers_layout' => $resource::relationManagersLayout(),
            'query' => $request->query(),
        ]);
    }

    public static function component(): string
    {
        return 'rocket/ViewRecord';
    }
}
