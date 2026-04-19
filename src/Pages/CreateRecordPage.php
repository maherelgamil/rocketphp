<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Panel\Panel;

class CreateRecordPage extends ResourcePage
{
    protected static function getAuthAbility(): string
    {
        return 'create';
    }

    public function handle(Request $request, Panel $panel): Response
    {
        $resource = $this->getResource();
        $resource::authorizeForRequest($request, 'create');

        $form = $resource::form(Form::make($resource));
        $indexUrl = $panel->url($resource::getSlug());
        $widgets = array_map(
            fn ($widget) => $widget->toArray(),
            $resource::getWidgets('create'),
        );

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
            'index_url' => $indexUrl,
            'widgets' => $widgets,
        ]);
    }

    public function component(): string
    {
        return 'rocket/CreateRecord';
    }

    public function can(Request $request): bool
    {
        $resource = $this->getResource();

        if ($resource === null) {
            return parent::can($request);
        }

        return $resource::can($request, 'create');
    }
}
