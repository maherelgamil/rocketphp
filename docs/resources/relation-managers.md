# Relation Managers

Relation managers render a related-records table on a resource's edit and
view pages — comments on a post, users on a team, items on an order. They
use the same [Table](../tables/columns.md) builder as resource list pages.

## Create a relation manager

Place the class under the resource's directory:

```
app/Rocket/Resources/PostResource/RelationManagers/CommentsRelationManager.php
```

```php
namespace App\Rocket\Resources\PostResource\RelationManagers;

use App\Models\Comment;
use MaherElGamil\Rocket\Resources\RelationManagers\RelationManager;
use MaherElGamil\Rocket\Tables\Columns\TextColumn;
use MaherElGamil\Rocket\Tables\Table;

final class CommentsRelationManager extends RelationManager
{
    public static function getRelationship(): string
    {
        return 'comments';
    }

    public static function getRelatedModel(): string
    {
        return Comment::class;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('author.name')->label('Author'),
            TextColumn::make('body'),
            TextColumn::make('created_at')->sortable(),
        ]);
    }
}
```

## Register on the resource

```php
public static function relationManagers(): array
{
    return [
        CommentsRelationManager::class,
    ];
}
```

## Contract

| Method | Required | Purpose |
| --- | --- | --- |
| `getRelationship()` | ✅ | Eloquent relationship method name on the parent model. |
| `getRelatedModel()` | ✅ | Related model class (used for authorization). |
| `table(Table $table)` | ✅ | Table definition for the related records. |
| `getName()` | ❌ | Tab name — defaults to snake-case of the relationship. |
| `getTitle()` | ❌ | Header label — defaults to headline-case. |
| `query(Model $owner)` | ❌ | Override the base query (e.g. add eager loads). |

## Pagination

Relation manager tables paginate at `pagination.relation_manager.per_page`
(default `5`) — configurable in `config/rocket.php`. See the
[configuration reference](../configuration.md).

## Supported relationships

Any Eloquent relationship that returns a query builder works —
`HasMany`, `HasManyThrough`, `BelongsToMany`, `MorphMany`. The relation
manager calls `$owner->{relationship}()->getQuery()` to fetch records.
