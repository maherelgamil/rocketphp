# Resources

A **Resource** maps an Eloquent model to a set of auto-generated CRUD pages:
list, create, edit, and view.

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
