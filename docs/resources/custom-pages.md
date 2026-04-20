# Custom Resource Pages

Beyond the four auto-generated CRUD pages (list, create, edit, view),
each resource can expose **custom pages** for workflows that don't fit
CRUD — a publish queue, a moderation inbox, a reporting view.

## Directory convention

Custom pages live under the resource's `Pages/` directory:

```
app/Rocket/Resources/
└── PostResource/
    ├── Pages/
    │   ├── PublishQueue.php
    │   └── ModerationInbox.php
    └── RelationManagers/
        └── ...
```

Rocket auto-discovers these and wires them up to the resource's route
group. No manual registration is required.

## Write a page

Extend `ResourcePage`:

```php
namespace App\Rocket\Resources\PostResource\Pages;

use MaherElGamil\Rocket\Pages\ResourcePage;
use MaherElGamil\Rocket\Pages\Blocks\WidgetBlock;
use MaherElGamil\Rocket\Dashboard\TableWidget;

final class PublishQueue extends ResourcePage
{
    protected static ?string $title = 'Publish queue';
    protected static ?string $slug = 'publish-queue';

    public function blocks(): array
    {
        $pending = \App\Models\Post::where('status', 'pending')->get();

        return [
            WidgetBlock::make(
                TableWidget::make('Pending posts', ['Title', 'Author'], $pending
                    ->map(fn ($p) => [$p->title, $p->author->name])
                    ->all()),
            ),
        ];
    }
}
```

## Routes

Custom resource pages mount at:

```
/{panel-path}/{resource-slug}/{page-slug}
```

So `PublishQueue` above is reachable at `/admin/posts/publish-queue`.

## Contract

| Member | Required | Default |
| --- | --- | --- |
| `$title` | ❌ | Uses the resource label. |
| `$slug` | ❌ | Kebab-case of the class name. |
| `blocks()` | ❌ | Returns `[]` (empty page). |
| `shouldRegisterNavigation()` | ❌ | Returns `false` — custom pages are **not** added to the sidebar by default. |

Override `shouldRegisterNavigation()` and return `true` if you want the
page to appear alongside the resource's main list link.

## Standalone pages (outside a resource)

For dashboard-style pages that don't belong to a resource, extend `Page`
directly and register via `->pages([MyPage::class])` or
`->discoverPages(in, for)` on the panel.

See [Page Blocks](../pages/blocks.md) for the building blocks available
to compose page content.
