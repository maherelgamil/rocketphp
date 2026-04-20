# Pages

A **Page** is the foundation of every screen in RocketPHP. Everything
you see in a panel — the dashboard, the list view, the edit form, a
custom report, the record detail — is a subclass of
`MaherElGamil\Rocket\Pages\Page`.

If Resources are the "what" (the model, the table, the form), Pages
are the "where" (the actual URL, the Inertia render, the navigation
entry). Pages are the unit of routing.

## The inheritance tree

```
Page (abstract)
├── DashboardPage           — panel dashboard (/{panel})
└── ResourcePage (abstract) — scoped to a Resource
    ├── ListRecordsPage     — /{panel}/{resource}
    ├── CreateRecordPage    — /{panel}/{resource}/create
    ├── EditRecordPage      — /{panel}/{resource}/{id}/edit
    ├── ViewRecordPage      — /{panel}/{resource}/{id}
    └── (your custom pages) — /{panel}/{resource}/{slug}
```

Every built-in CRUD screen ships as one of these subclasses. When you
write a custom page you pick the right base class and override only
what differs.

## The SDUI lifecycle

When a page is requested:

1. Laravel routes the request to the page's handler.
2. `mount(Request $request)` runs — your hook for loading data,
   authorization, or setting `$this->record`.
3. `actions()` is serialized to the page toolbar.
4. `content(Panel $panel)` returns an array of blocks; each block's
   `toArray()` is called.
5. `handle()` wraps it all in `Inertia::render($component, [...])`
   with shared panel props, page metadata, actions, and content.
6. React receives the schema and renders it deterministically.

No state lives on the client. The page is a pure function of the
request.

## Generating a page

```bash
php artisan rocket:make-page {name} [--resource=]
```

| Form | Destination | Namespace |
| --- | --- | --- |
| `rocket:make-page Settings` | `app/Rocket/Pages/SettingsPage.php` | `App\Rocket\Pages` |
| `rocket:make-page AuditLog --resource=Post` | `app/Rocket/Resources/PostResource/Pages/AuditLogPage.php` | `App\Rocket\Resources\PostResource\Pages` |

The `Page` suffix is appended automatically. The stub extends
`Page` and provides an empty `content()` for you to fill in.

Customise the generated template by publishing stubs:

```bash
php artisan vendor:publish --tag=rocket-stubs
# edit stubs/rocket/page.stub
```

## The Page API

Every page inherits these hooks from `Page`. Override only the ones
that matter to your screen.

### Routing & identity

| Method | Default | Override when |
| --- | --- | --- |
| `getSlug()` | kebab-cased class basename | The URL segment should differ from the class name. |
| `component()` | `'rocket/Page'` | You ship a custom React renderer. |

### Navigation

| Method | Default | Override when |
| --- | --- | --- |
| `getNavigationLabel()` | `getTitle()` | Sidebar label should differ from title. |
| `getNavigationIcon()` | `null` | You want a lucide icon in the sidebar. |
| `getNavigationGroup()` | `null` | Group the entry under a heading. |
| `getNavigationSort()` | `0` | Force ordering relative to siblings. |
| `shouldRegisterNavigation()` | `true` | Hide the page from the sidebar (still routable). |

### Content

| Method | Default | Override when |
| --- | --- | --- |
| `getTitle()` | titleized class basename minus `Page` | You want a static or dynamic title. |
| `getSubtitle()` | `null` | A subtitle improves the header. |
| `actions()` | `[]` | Add toolbar buttons — see [Page Actions](actions.md). |
| `content(Panel $panel)` | `[]` | Always — this is the body. Return an array of [blocks](blocks.md). |

### Lifecycle & security

| Method | Default | Override when |
| --- | --- | --- |
| `mount(Request)` | no-op | Load `$this->record`, validate params, fire side-effects. |
| `can(Request)` | `true` | Gate the page behind a policy or ability. |

## Kinds of pages you'll write

### A dashboard-style page

Extend `DashboardPage` (or `Page` directly) when the screen is not
tied to a single record. Compose it from
[blocks](blocks.md) — widgets, grids, HTML.

```php
use MaherElGamil\Rocket\Pages\DashboardPage;
use MaherElGamil\Rocket\Pages\Blocks\GridBlock;
use MaherElGamil\Rocket\Pages\Blocks\WidgetBlock;
use MaherElGamil\Rocket\Panel\Panel;

final class OpsHealthPage extends DashboardPage
{
    public function getTitle(): string
    {
        return 'Ops Health';
    }

    public function content(Panel $panel): array
    {
        return [
            GridBlock::make()->columns(3)->schema([
                WidgetBlock::make(QueueLagWidget::make()),
                WidgetBlock::make(ErrorRateWidget::make()),
                WidgetBlock::make(UptimeWidget::make()),
            ]),
        ];
    }
}
```

Register it on your panel via `->pages([OpsHealthPage::class])` or
with `->discoverPages(in: ..., namespace: ...)`.

### A resource-scoped page

Extend `ResourcePage` when the screen belongs to a resource — a
moderation inbox on `PostResource`, a billing report on
`InvoiceResource`. `ResourcePage` automatically pulls its label,
icon, navigation group, and authorization from the owning resource.
See [Custom Resource Pages](../resources/custom-pages.md).

### A custom renderer

If your page needs a hand-rolled React view, return a different
component name from `component()` and mount the React page under
the same key in your host app's `resources/js/pages/`. The shared
`panel`, `actions`, and `content` props are still passed through, so
you can reuse the layout while taking over the body.

## Registering pages on a panel

```php
use App\Rocket\Pages\OpsHealthPage;
use MaherElGamil\Rocket\Panel\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->path('admin')
        ->pages([OpsHealthPage::class])
        // or auto-discover everything in a directory:
        ->discoverPages(
            directory: app_path('Rocket/Pages'),
            namespace: 'App\\Rocket\\Pages',
        );
}
```

Resource-scoped pages are auto-discovered from each resource's
`Pages/` subdirectory — no extra registration needed.

## Related

- [Page Blocks](blocks.md) — the composable schema fragments pages
  return from `content()`.
- [Custom Resource Pages](../resources/custom-pages.md) — pages that
  live inside a resource.
- [Server-Driven UI](../advanced/server-driven-ui.md) — the broader
  pattern Pages implement.
