# Page Blocks

Custom pages (dashboards, reports, landing screens) are built from
**blocks**: reusable schema fragments that compose into full pages.

```php
use MaherElGamil\Rocket\Pages\Blocks\GridBlock;
use MaherElGamil\Rocket\Pages\Blocks\HtmlBlock;
use MaherElGamil\Rocket\Pages\Blocks\WidgetBlock;
use MaherElGamil\Rocket\Pages\DashboardPage;

final class MarketingDashboard extends DashboardPage
{
    public static function blocks(): array
    {
        return [
            HtmlBlock::make('<h1 class="text-2xl">Marketing</h1>'),

            GridBlock::make()
                ->columns(3)
                ->schema([
                    WidgetBlock::make(StatWidget::make('Signups', 4_212)),
                    WidgetBlock::make(StatWidget::make('Trials', 318)),
                    WidgetBlock::make(StatWidget::make('MRR', '$82k')),
                ]),
        ];
    }
}
```

## Block types

| Class | Purpose |
| --- | --- |
| `WidgetBlock` | Render a dashboard widget inside a page. |
| `GridBlock` | Multi-column layout container (1–6 columns). |
| `HtmlBlock` | Raw HTML content — for headings, callouts, or notes. |

## Composition

Blocks can be nested. A `GridBlock` can contain widgets, HTML, or even
other grids. Each block is an independent schema object, so the React
renderer composes them without any page-specific code.

## Adding a custom block

1. Subclass a base block class in `src/Pages/Blocks/`
2. Implement `toArray(): array` to serialize the schema
3. Add a matching React renderer in `block-renderer.tsx`
4. Cover the serialization in a test under `tests/Feature/Pages/`

Same three-move pattern as [custom columns](../tables/columns.md#adding-a-custom-column).
See [Server-Driven UI](../advanced/server-driven-ui.md) for the broader
pattern this follows.
