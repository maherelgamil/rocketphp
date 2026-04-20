# Panel Configuration

A `Panel` is a fluent configuration object that scopes routes, resources,
middleware, and theming. Panels are defined in a `PanelProvider` subclass.

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

## Routing

| Method | Purpose |
| --- | --- |
| `->path(string)` | URL prefix for all panel routes (default: `admin`). |
| `->domain(?string)` | Restrict the panel to a specific domain. |
| `->middleware(array)` | Route middleware applied to all panel routes. |
| `->authMiddleware(array)` | Middleware applied to authenticated routes. |
| `->guard(string)` | Auth guard (default: `web`). |

## Branding & theme

| Method | Purpose |
| --- | --- |
| `->brand(string)` | Brand name shown in the sidebar header. |
| `->setPrimaryColor(string)` | Primary color (hex). |
| `->setAccentColor(string)` | Accent color (hex). |
| `->setFont(string)` | Font family. |
| `->setRadius(string)` | Base border radius. |
| `->setDensity(string)` | `default` · `compact` · `extra-compact`. |

## Layout

| Method | Purpose |
| --- | --- |
| `->dashboardColumns(int)` | Widget grid columns (1–6). |
| `->sidebarCollapsible(bool)` | Show/hide the collapse toggle. |
| `->sidebarCollapsed(bool)` | Start the sidebar collapsed. |

## Features

| Method | Purpose |
| --- | --- |
| `->globalSearchEnabled(bool)` | Toggle the `Cmd+K` global search. |
| `->globalSearchPlaceholder(string)` | Custom placeholder text. |
| `->notificationsEnabled(bool)` | Toggle the notifications bell. |

## Internationalization

| Method | Purpose |
| --- | --- |
| `->locale(string)` | Default locale. |
| `->availableLocales(array)` | Locales shown in the switcher. |

See [Internationalization & RTL](../i18n.md) for the full i18n guide.

## Discovery

| Method | Purpose |
| --- | --- |
| `->discoverResources(in, for)` | Auto-register resources from a directory. |
| `->discoverPages(in, for)` | Auto-register custom pages. |

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
