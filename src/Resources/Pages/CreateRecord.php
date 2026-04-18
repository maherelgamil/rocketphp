<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Resources\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Panel\Panel;

class CreateRecord extends Page
{
    public function handle(Request $request, Panel $panel, string $resource): Response
    {
        $resource::authorizeForRequest($request, 'create');

        $form = $resource::form(Form::make($resource));

        return Inertia::render(static::component(), [
            'panel' => $panel->toSharedProps(),
            'resource' => [
                'slug' => $resource::getSlug(),
                'label' => $resource::getLabel(),
                'pluralLabel' => $resource::getPluralLabel(),
                'can' => [
                    'create' => $resource::can($request, 'create'),
                ],
            ],
            'form' => $form->toArray(),
            'record' => null,
            'state' => $form->getDefaults(),
            'action' => [
                'method' => 'post',
                'url' => $panel->url($resource::getSlug()),
            ],
            'index_url' => $panel->url($resource::getSlug()),
        ]);
    }

    public static function component(): string
    {
        return 'rocket/CreateRecord';
    }
}
