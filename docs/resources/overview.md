# Resources

A **Resource** maps an Eloquent model to a set of auto-generated CRUD pages:
list, create, edit, and view.

## Generate a resource

```bash
php artisan rocket:make-resource {name} [--model=]
```

Generates `app/Rocket/Resources/{Name}Resource.php` bound to
`App\Models\{Name}` by default. The stub includes an `id` column and
`searchable(['id'])` to start from.

```bash
# Assumes App\Models\Post
php artisan rocket:make-resource Post

# Custom model namespace
php artisan rocket:make-resource Invoice --model='Domain\Billing\Invoice'

# Name already has the Resource suffix
php artisan rocket:make-resource PostResource
```

## Anatomy

```php
use App\Models\Post;
use MaherElGamil\Rocket\Resources\Resource;

final class PostResource extends Resource
{
    protected static string $model = Post::class;

    protected static ?string $slug = 'posts';             // URL slug
    protected static ?string $navigationIcon = 'file';    // Sidebar icon
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table { /* ... */ }
    public static function form(Form $form): Form { /* ... */ }
    public static function widgets(): array { return []; }
    public static function relationManagers(): array { return []; }
}
```

## What's required?

Only `$model`. Everything else has a sensible default.

| Member | Required | Default |
| --- | --- | --- |
| `$model` | ✅ | — |
| `$slug` | ❌ | Kebab-case plural of the class name. |
| `$navigationIcon` | ❌ | Generic icon. |
| `$navigationGroup` | ❌ | Ungrouped (shown at root of sidebar). |
| `$navigationSort` | ❌ | Alphabetical within group. |
| `table()` | ❌ | Empty table — you'll want this for the list page. |
| `form()` | ❌ | No form → no create/edit pages, **New** button is hidden. |
| `widgets()` | ❌ | No widgets on resource pages. |
| `relationManagers()` | ❌ | No relation managers on edit/view. |
| `globalSearchColumns()` | ❌ | `[]` → resource is opted out of global search. |

## Registering a resource on a panel

Two options — both optional, additive, and composable.

**1. Auto-discover** from a directory:

```php
$panel->discoverResources(
    in: app_path('Rocket/Resources'),
    for: 'App\\Rocket\\Resources',
);
```

**2. Explicitly list** the resource classes:

```php
$panel->resources([
    App\Rocket\Resources\UserResource::class,
    App\Rocket\Resources\PostResource::class,
]);
```

You can use both on the same panel — useful when most resources live in a
discoverable directory but a few live elsewhere.

## CRUD pages

Every resource gets four pages automatically:

| Route | Page | Policy checked |
| --- | --- | --- |
| `GET  /{slug}` | List | `viewAny` |
| `GET  /{slug}/create` | Create | `create` |
| `GET  /{slug}/{record}` | View | `view` |
| `GET  /{slug}/{record}/edit` | Edit | `update` |

Pages are only rendered if the policy allows. If `viewAny` is denied, the
resource is hidden from the sidebar entirely.

## Custom pages

Drop a page class into `app/Rocket/Resources/{Resource}/Pages/` and extend
`ResourcePage`. Rocket discovers and registers it automatically.

```php
namespace App\Rocket\Resources\PostResource\Pages;

use MaherElGamil\Rocket\Pages\ResourcePage;

final class PublishQueue extends ResourcePage
{
    protected static ?string $title = 'Publish queue';
    protected static ?string $slug = 'publish-queue';
}
```

## Relation managers

Manage related records (HasMany, BelongsToMany, etc.) on the edit/view page:

```php
public static function relationManagers(): array
{
    return [
        CommentsRelationManager::class,
    ];
}
```

See also:

- [Tables](../tables/columns.md)
- [Forms](../forms/fields.md)
- [Widgets](../widgets/overview.md)
- [Authorization](../authorization.md)
