# Widgets

Widgets are small UI components that render a single piece of data —
counters, charts, recent records, activity feeds. They appear on the
dashboard and on resource pages (list, create, edit, view).

## Declaring widgets on a resource

```php
use MaherElGamil\Rocket\Dashboard\RecentRecordsWidget;
use MaherElGamil\Rocket\Dashboard\StatWidget;

public static function widgets(): array
{
    return [
        StatWidget::make('Total Users', User::query()->count())
            ->columnSpan(3)
            ->only(['list']),

        RecentRecordsWidget::make('Recent Users')
            ->columnSpan(3)
            ->resource(UserResource::class)
            ->limit(5),
    ];
}
```

## Widget types

| Class | Purpose |
| --- | --- |
| `StatWidget` | Single value with a label (count, total, status). |
| `ChartWidget` | Line / bar / area chart. |
| `TableWidget` | Ad-hoc rows + columns table. |
| `RecentRecordsWidget` | Latest N records from a resource. |
| `ActivityFeedWidget` | Chronological list of events. |

## Common modifiers

- `->columnSpan(int)` — width in the dashboard grid (1–6; panel-configured max)
- `->only(array)` — restrict to specific resource pages (`list`, `create`, `edit`, `view`)
- Omitting `->only()` renders the widget on every page

## Placement

Widgets declared in `Resource::widgets()` appear on that resource's pages.
For the main dashboard, register widgets on the panel or via a custom
`DashboardPage`.

See [Panel Configuration](../panels/configuration.md#layout) for the
`dashboardColumns()` setting.
