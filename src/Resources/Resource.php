<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Pages\CreateRecordPage;
use MaherElGamil\Rocket\Pages\EditRecordPage;
use MaherElGamil\Rocket\Pages\ListRecordsPage;
use MaherElGamil\Rocket\Pages\ResourcePage;
use MaherElGamil\Rocket\Pages\ViewRecordPage;
use MaherElGamil\Rocket\Resources\RelationManagers\RelationManager;
use MaherElGamil\Rocket\Tables\Table;
use Symfony\Component\Finder\Finder;

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
     * Discover custom page classes in a directory.
     *
     * Scans for PHP classes extending ResourcePage for this resource.
     * Pages can override standard CRUD pages (index, create, edit, view).
     *
     * @return array<string, class-string<ResourcePage>>
     */
    public static function discoverPages(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $pages = [];
        $realDirectory = realpath($directory) ?: $directory;
        $files = Finder::create()->in($directory)->files()->name('*.php');

        foreach ($files as $file) {
            $relativePath = str_replace($realDirectory.DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $class = str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relativePath
            );

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(ResourcePage::class)) {
                continue;
            }

            $slug = (new $class)->getSlug();
            $pages[$slug] = $class;
        }

        return $pages;
    }

    /**
     * @return array<string, class-string<ResourcePage>>
     */
    public static function getPages(): array
    {
        $pages = [
            'index' => ListRecordsPage::class,
            'create' => CreateRecordPage::class,
            'edit' => EditRecordPage::class,
            'view' => ViewRecordPage::class,
        ];

        return array_merge($pages, static::discoverPagesInApp());
    }

    /**
     * Discover custom pages in the app's Resource/Pages directory.
     *
     * Convention: `app/Rocket/Resources/{ResourceBasename}/Pages/`
     * with namespace `App\Rocket\Resources\{ResourceBasename}\Pages\`.
     *
     * Override this method to provide custom discovery location.
     *
     * @return array<string, class-string<ResourcePage>>
     */
    protected static function discoverPagesInApp(): array
    {
        $basename = class_basename(static::class);
        $directory = app_path('Rocket'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.$basename.DIRECTORY_SEPARATOR.'Pages');
        $namespace = 'App\\Rocket\\Resources\\'.$basename.'\\Pages';

        if (! is_dir($directory)) {
            return [];
        }

        $reserved = ['index', 'create', 'edit', 'view'];
        $pages = [];
        $realDirectory = realpath($directory) ?: $directory;

        $files = Finder::create()->in($directory)->files()->name('*.php');

        foreach ($files as $file) {
            $relativePath = str_replace($realDirectory.DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $class = $namespace.'\\'.str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relativePath
            );

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(ResourcePage::class)) {
                continue;
            }

            $slug = (new $class)->getCustomPageSlug();

            if (in_array($slug, $reserved, true)) {
                continue;
            }

            $pages[$slug] = $class;
        }

        return $pages;
    }

    abstract public static function table(Table $table): Table;

    public static function form(Form $form): Form
    {
        return $form;
    }

    /**
     * Define widgets to display on resource pages.
     *
     * @return array<int, object>
     */
    public static function widgets(): array
    {
        return [];
    }

    /**
     * Get widgets for a specific page.
     *
     * @return array<int, object>
     */
    public static function getWidgets(string $page): array
    {
        $widgets = static::widgets();

        return array_filter($widgets, fn ($widget) => method_exists($widget, 'shouldRenderOnPage') && $widget->shouldRenderOnPage($page));
    }

    /**
     * @return array<int, class-string<RelationManager>>
     */
    public static function relationManagers(): array
    {
        return [];
    }

    /**
     * Layout used to render relation managers on edit / view pages.
     *
     * Supported: "tabs" (default) and "stacked".
     */
    public static function relationManagersLayout(): string
    {
        return 'tabs';
    }

    /**
     * Columns searched during global search. Return [] to opt this resource out.
     *
     * @return array<int, string>
     */
    public static function globalSearchColumns(): array
    {
        return [];
    }

    /**
     * Shape: { title, description? }. The controller appends 'url'.
     *
     * @return array<string, mixed>
     */
    public static function globalSearchResult(Model $record): array
    {
        return [
            'title' => (string) $record->getKey(),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::query();
    }

    /**
     * FQCN of the Exporter for this resource, or null when exports are disabled.
     *
     * @return class-string<\MaherElGamil\Rocket\Exports\Exporter>|null
     */
    public static function exporter(): ?string
    {
        return null;
    }

    /**
     * FQCN of the Importer for this resource, or null when imports are disabled.
     *
     * @return class-string<\MaherElGamil\Rocket\Imports\Importer>|null
     */
    public static function importer(): ?string
    {
        return null;
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
