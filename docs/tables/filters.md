# Filters

Filters narrow a table's result set. They appear above the table as
dropdowns or toggles and persist in the URL query string.

```php
use MaherElGamil\Rocket\Tables\Filters\DateRangeFilter;
use MaherElGamil\Rocket\Tables\Filters\SelectFilter;
use MaherElGamil\Rocket\Tables\Filters\TernaryFilter;
use MaherElGamil\Rocket\Tables\Filters\TrashedFilter;

$table->filters([
    SelectFilter::make('status')
        ->options([
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ]),

    TernaryFilter::make('is_featured')
        ->label('Featured'),

    DateRangeFilter::make('created_at'),

    TrashedFilter::make(),
]);
```

## `SelectFilter`

Single-select dropdown. Pass a `key => label` map via `->options()`.

## `TernaryFilter`

Three-state toggle: **Yes**, **No**, **All**. Backed by a boolean column.

## `DateRangeFilter`

Two date pickers that filter with `whereBetween`.

## `TrashedFilter`

Soft-delete filter with three states:

- **Active only** (default) — `whereNull('deleted_at')`
- **Trashed only** — `onlyTrashed()`
- **All** — `withTrashed()`

Only include this on resources whose model uses `SoftDeletes`.

## Custom filter logic

Every filter supports `->query(Closure $callback)` to override how it
applies to the Eloquent query. Use this when the filter key doesn't map
one-to-one with a column.

```php
SelectFilter::make('activity')
    ->options(['active' => 'Active', 'stale' => 'Stale'])
    ->query(function ($query, $value) {
        return match ($value) {
            'active' => $query->where('last_seen_at', '>', now()->subDays(7)),
            'stale'  => $query->where('last_seen_at', '<=', now()->subDays(7)),
            default  => $query,
        };
    });
```
