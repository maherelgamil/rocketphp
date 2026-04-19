<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Pages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class ResourcePage extends Page
{
    protected ?string $resource = null;

    protected ?Model $record = null;

    public function resource(string $class): static
    {
        $this->resource = $class;

        return $this;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function getRecord(): ?Model
    {
        return $this->record;
    }

    public function setRecord(?Model $record): static
    {
        $this->record = $record;

        return $this;
    }

    public function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        $resource = $this->getResource();

        return $resource ? $resource::getLabel() : parent::getTitle();
    }

    public function getSlug(): string
    {
        $resource = $this->getResource();

        return $resource ? $resource::getSlug() : parent::getSlug();
    }

    public function getNavigationLabel(): string
    {
        $resource = $this->getResource();

        return $resource ? $resource::getPluralLabel() : parent::getNavigationLabel();
    }

    public function getNavigationIcon(): ?string
    {
        $resource = $this->getResource();

        return $resource ? $resource::getNavigationIcon() : parent::getNavigationIcon();
    }

    public function getNavigationGroup(): ?string
    {
        $resource = $this->getResource();

        return $resource ? $resource::getNavigationGroup() : parent::getNavigationGroup();
    }

    public function getNavigationSort(): int
    {
        $resource = $this->getResource();

        return $resource ? $resource::getNavigationSort() : parent::getNavigationSort();
    }

    public function can(Request $request): bool
    {
        $resource = $this->getResource();

        if ($resource === null) {
            return parent::can($request);
        }

        return $resource::can($request, static::getAuthAbility());
    }

    protected static function getAuthAbility(): string
    {
        return 'viewAny';
    }

    /**
     * Slug used to reach this page as a custom resource page
     * at `/{panel}/{resourceSlug}/{customPageSlug}`.
     *
     * Defaults to the kebab-cased class basename with the "Page" suffix stripped.
     * E.g. `StatsPage` => `stats`.
     */
    public function getCustomPageSlug(): string
    {
        $base = str_replace('Page', '', class_basename(static::class));

        return Str::kebab($base);
    }
}
