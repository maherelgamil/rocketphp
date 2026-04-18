<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use MaherElGamil\Rocket\Resources\Pages\ListRecords;
use MaherElGamil\Rocket\Tables\Table;

abstract class Resource
{
    /** @var class-string<Model> */
    protected static string $model;

    protected static ?string $slug = null;

    protected static ?string $label = null;

    protected static ?string $pluralLabel = null;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = null;

    protected static int $navigationSort = 0;

    /**
     * @return class-string<Model>
     */
    public static function getModel(): string
    {
        return static::$model;
    }

    public static function query(): Builder
    {
        return static::$model::query();
    }

    public static function getSlug(): string
    {
        if (static::$slug !== null) {
            return static::$slug;
        }

        $base = Str::of(class_basename(static::class))
            ->replaceLast('Resource', '')
            ->kebab()
            ->plural();

        return (string) $base;
    }

    public static function getLabel(): string
    {
        return static::$label
            ?? (string) Str::of(class_basename(static::$model))->snake(' ')->title();
    }

    public static function getPluralLabel(): string
    {
        return static::$pluralLabel ?? Str::plural(static::getLabel());
    }

    public static function getNavigationIcon(): ?string
    {
        return static::$navigationIcon;
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup;
    }

    public static function getNavigationSort(): int
    {
        return static::$navigationSort;
    }

    /**
     * @return array<string, class-string<\MaherElGamil\Rocket\Resources\Pages\Page>>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRecords::class,
        ];
    }

    abstract public static function table(Table $table): Table;
}
