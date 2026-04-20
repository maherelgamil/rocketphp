# Panel Configuration

A `Panel` is a fluent configuration object that scopes routes, resources,
middleware, and theming. Panels are defined in a `PanelProvider` subclass.

## Generate a panel

```bash
php artisan rocket:make-panel {name}
```

Creates `app/Providers/Rocket/{Name}PanelProvider.php` pre-wired to the
`app/Rocket/Resources/` discovery path.

```bash
php artisan rocket:make-panel Admin
```

Register the generated provider in `bootstrap/providers.php`:

```php
App\Providers\Rocket\AdminPanelProvider::class,
```

The `PanelProvider` suffix is appended automatically — all three of these
generate the same file: `Admin`, `AdminPanel`, `AdminPanelProvider`.


```php
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelProvider;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->path('admin')
            ->brand('My App');
    }
}
```

## What's required?

**Nothing.** A panel with just `return $panel;` is valid — it boots with
sensible defaults pulled from `config/rocket.php`. Every method below is
optional and shown with its default value.

All methods are optional. The tables below list defaults and notes.

## Routing

| Method | Default | Notes |
| --- | --- | --- |
| `->path(string)` | `'admin'` | URL prefix for all panel routes. |
| `->domain(?string)` | `config('rocket.routes.domain')` (usually `null`) | Restrict the panel to a specific domain. |
| `->middleware(array)` | `config('rocket.routes.middleware')` → `['web']` | Middleware for all panel routes. |
| `->authMiddleware(array)` | `config('rocket.routes.auth_middleware')` → `['auth']` | Middleware for authenticated routes. |
| `->guard(string)` | `'web'` | Auth guard. |
| `->default()` | panel is not default | Mark this panel as the fallback when multiple are registered. |

## Branding & theme

| Method | Default | Notes |
| --- | --- | --- |
| `->brand(string)` | `config('rocket.brand.name')` → `'Rocket'` | Brand name shown in the sidebar header. |
| `->setPrimaryColor(string)` | theme default | Primary color (hex). |
| `->setAccentColor(string)` | theme default | Accent color (hex). |
| `->setFont(string)` | theme default | Font family. |
| `->setRadius(string)` | theme default | Base border radius. |
| `->setDensity(string)` | `'default'` | `default` · `compact` · `extra-compact`. |

## Layout

| Method | Default | Notes |
| --- | --- | --- |
| `->dashboardColumns(int)` | `3` | Widget grid columns (1–6). |
| `->sidebarCollapsible(bool)` | `true` | Show/hide the collapse toggle. |
| `->sidebarCollapsed(bool)` | `null` (expanded) | Start the sidebar collapsed; requires `sidebarCollapsible(true)`. |

## Features

| Method | Default | Notes |
| --- | --- | --- |
| `->globalSearchEnabled(bool)` | `true` | Toggle the `Cmd+K` global search. |
| `->globalSearchPlaceholder(string)` | `'Search...'` | Custom placeholder text. |
| `->notificationsEnabled(bool)` | `false` | Toggle the notifications bell. |

## Internationalization

| Method | Default | Notes |
| --- | --- | --- |
| `->locale(string)` | `app()->getLocale()` | Default locale. |
| `->availableLocales(array)` | `[]` (switcher hidden) | Locales shown in the switcher. |

See [Internationalization & RTL](../i18n.md) for the full i18n guide.

## Resources & pages

Resources and pages are registered in one of three ways — or not at all.
A panel with no resources is valid (e.g. a dashboard-only panel).

| Method | Default | Notes |
| --- | --- | --- |
| `->resources(array)` | `[]` | Explicit list of resource class names. Additive: can be called multiple times. |
| `->discoverResources(in, for)` | not called | Auto-discover resources in a directory. Can be combined with `resources()`. |
| `->pages(array)` | `[]` | Explicit list of custom page class names. |
| `->discoverPages(in, for)` | not called | Auto-discover pages in a directory. |

Example of combining:

```php
return $panel
    ->discoverResources(
        in: app_path('Rocket/Resources'),
        for: 'App\\Rocket\\Resources',
    )
    ->resources([
        App\Other\BillingResource::class, // outside the discovery path
    ]);
```

## Full example

```php
return $panel
    ->default()
    ->path('admin')
    ->brand('My App')
    ->domain('admin.example.com')
    ->middleware(['web'])
    ->authMiddleware(['auth', 'verified'])
    ->guard('web')
    ->dashboardColumns(4)
    ->globalSearchEnabled(true)
    ->globalSearchPlaceholder('Search...')
    ->notificationsEnabled(true)
    ->sidebarCollapsible(true)
    ->setPrimaryColor('#3b82f6')
    ->setAccentColor('#8b5cf6')
    ->setFont('Inter')
    ->setRadius('0.5rem')
    ->setDensity('default')
    ->locale('en')
    ->availableLocales(['en', 'ar'])
    ->discoverResources(
        in: app_path('Rocket/Resources'),
        for: 'App\\Rocket\\Resources',
    );
```
