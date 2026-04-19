<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Pages;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use MaherElGamil\Rocket\Panel\Panel;

abstract class Page
{
    public function getSlug(): string
    {
        return Str::kebab(class_basename(static::class));
    }

    public function getTitle(): string
    {
        return trim(Str::title(str_replace('Page', '', class_basename(static::class))));
    }

    public function getSubtitle(): ?string
    {
        return null;
    }

    public function component(): string
    {
        return 'rocket/Page';
    }

    public function getNavigationLabel(): string
    {
        return $this->getTitle();
    }

    public function getNavigationIcon(): ?string
    {
        return null;
    }

    public function getNavigationGroup(): ?string
    {
        return null;
    }

    public function getNavigationSort(): int
    {
        return 0;
    }

    public function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function can(Request $request): bool
    {
        return true;
    }

    public function mount(Request $request): void {}

    /**
     * @return array<int, mixed>
     */
    public function actions(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function content(Panel $panel): array
    {
        return [];
    }

    public function handle(Request $request, Panel $panel): Response
    {
        $this->mount($request);

        return Inertia::render($this->component(), [
            'panel' => $panel->toSharedProps(),
            'page' => [
                'title' => $this->getTitle(),
                'subtitle' => $this->getSubtitle(),
                'slug' => $this->getSlug(),
            ],
            'actions' => array_map(fn ($a) => $a->toArray(), $this->actions()),
            'content' => array_map(fn ($b) => $b->toArray(), $this->content($panel)),
        ]);
    }
}
