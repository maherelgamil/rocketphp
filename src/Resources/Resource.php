<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Resources\Pages\CreateRecord;
use MaherElGamil\Rocket\Resources\Pages\EditRecord;
use MaherElGamil\Rocket\Resources\Pages\ListRecords;
use MaherElGamil\Rocket\Resources\Pages\ViewRecord;
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
            'create' => CreateRecord::class,
            'edit' => EditRecord::class,
            'view' => ViewRecord::class,
        ];
    }

    abstract public static function table(Table $table): Table;

    public static function form(Form $form): Form
    {
        return $form;
    }

    /**
     * @return array<int, class-string<\MaherElGamil\Rocket\Resources\RelationManagers\RelationManager>>
     */
    public static function relationManagers(): array
    {
        return [];
    }

    public static function hasForm(): bool
    {
        return static::form(Form::make(static::class))->getSchema() !== [];
    }

    /**
     * Authorize a policy ability for the resource model (abort403 on failure).
     *
     * @param  'viewAny'|'view'|'create'|'update'|'delete'  $ability
     */
    public static function authorizeForRequest(Request $request, string $ability, ?Model $model = null): void
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $modelClass = static::getModel();
        $gate = Gate::forUser($user);

        match ($ability) {
            'viewAny' => $gate->authorize('viewAny', $modelClass),
            'view' => $gate->authorize('view', $model ?? throw new \InvalidArgumentException('Model required for view authorization.')),
            'create' => $gate->authorize('create', $modelClass),
            'update' => $gate->authorize('update', $model ?? throw new \InvalidArgumentException('Model required for update authorization.')),
            'delete' => $gate->authorize('delete', $model ?? throw new \InvalidArgumentException('Model required for delete authorization.')),
            default => throw new \InvalidArgumentException("Unknown Rocket authorization ability [{$ability}]."),
        };
    }

    /**
     * @param  'viewAny'|'view'|'create'|'update'|'delete'  $ability
     */
    public static function can(Request $request, string $ability, ?Model $model = null): bool
    {
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        $modelClass = static::getModel();
        $gate = Gate::forUser($user);

        return match ($ability) {
            'viewAny' => $gate->check('viewAny', $modelClass),
            'view' => $model !== null && $gate->check('view', $model),
            'create' => $gate->check('create', $modelClass),
            'update' => $model !== null && $gate->check('update', $model),
            'delete' => $model !== null && $gate->check('delete', $model),
            default => false,
        };
    }
}
