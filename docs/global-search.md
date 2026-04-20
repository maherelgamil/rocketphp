# Global Search

Rocket provides a keyboard-driven global search (`Cmd+K` / `Ctrl+K`) in
the panel header. Resources opt in by declaring which columns to search
and how to format each result.

## Enable on a panel

```php
$panel
    ->globalSearchEnabled(true)
    ->globalSearchPlaceholder('Search users, posts...');
```

## Opt a resource in

Override two methods on your Resource:

```php
use Illuminate\Database\Eloquent\Model;
use MaherElGamil\Rocket\Resources\Resource;

final class UserResource extends Resource
{
    public static function globalSearchColumns(): array
    {
        return ['name', 'email'];
    }

    public static function globalSearchResult(Model $record): array
    {
        return [
            'title' => $record->name,
            'description' => $record->email,
        ];
    }
}
```

Returning an empty array from `globalSearchColumns()` opts the resource
out of search. The controller appends a `url` field to each result that
points to the resource's view page.

## Scoping the search query

Override `getGlobalSearchEloquentQuery()` to customize the base query —
useful for hiding soft-deleted records, applying team scoping, or
eager-loading.

```php
public static function getGlobalSearchEloquentQuery(): Builder
{
    return static::query()->where('team_id', auth()->user()->current_team_id);
}
```

## Result shape

Each result is serialized as:

```json
{
  "title": "Jane Cooper",
  "description": "jane@example.com",
  "url": "/admin/users/42"
}
```

The React renderer displays the title, an optional description, and links
to the URL on select.
