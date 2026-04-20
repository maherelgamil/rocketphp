# Configuration Reference

`config/rocket.php` holds package-wide defaults. Every key is optional;
panels can override any value through the fluent [Panel API](panels/configuration.md).

Publish the file to customize defaults:

```bash
php artisan vendor:publish --tag=rocket-config
```

## Default panel

| Key | Default | Purpose |
| --- | --- | --- |
| `default_panel` | `env('ROCKET_DEFAULT_PANEL', 'admin')` | Panel ID used when no panel is explicitly resolved. |

If you register multiple panels, this is the fallback. You can also mark
one as default with `$panel->default()`.

## Inertia

| Key | Default | Purpose |
| --- | --- | --- |
| `inertia.root_view` | `env('ROCKET_ROOT_VIEW', 'rocket::app')` | Blade view that wraps every Rocket page. Publish `rocket-views` to override. |

## Assets

| Key | Default | Purpose |
| --- | --- | --- |
| `assets.js_entry` | `env('ROCKET_JS_ENTRY', 'packages/rocketphp/resources/js/rocket.tsx')` | Vite entry included in the root Blade view. Change this if you've published Rocket's JS with `rocket-assets`. |

## Routes

| Key | Default | Purpose |
| --- | --- | --- |
| `routes.middleware` | `['web']` | Middleware applied to every panel route. |
| `routes.auth_middleware` | `['auth']` | Middleware applied to authenticated routes only. |
| `routes.domain` | `null` | Restrict all panels to a domain. |

Panels can override each of these with `->middleware()`,
`->authMiddleware()`, and `->domain()`.

## Pagination

| Key | Default | Purpose |
| --- | --- | --- |
| `pagination.per_page` | `25` | Default page size on list pages. |
| `pagination.min_per_page` | `1` | Hard floor for the `perPage` query param. |
| `pagination.max_per_page` | `100` | Hard ceiling — protects against abusive page sizes. |
| `pagination.per_page_options` | `[5, 10, 25, 50, 100]` | Choices shown in the per-page dropdown. |
| `pagination.relation_manager.per_page` | `5` | Page size inside [relation managers](resources/relation-managers.md). |

## Branding

| Key | Default | Purpose |
| --- | --- | --- |
| `brand.name` | `env('ROCKET_BRAND', 'Rocket')` | Default brand text; overridable per panel with `->brand()`. |

## Publishable tags

Rocket publishes its resources under separate tags so you can pick only
what you need.

| Tag | Destination | Use |
| --- | --- | --- |
| `rocket-config` | `config/rocket.php` | Change defaults (pagination, brand, routes). |
| `rocket-lang` | `lang/vendor/rocket/{locale}.json` | Override translations. See [i18n](i18n.md). |
| `rocket-views` | `resources/views/vendor/rocket/` | Override the Blade root layout. |
| `rocket-assets` | `resources/js/vendor/rocketphp/` | Fork the React frontend. |
| `rocket-stubs` | `stubs/rocket/` | Customise the templates used by `rocket:make-*` generators. |

Publish a single tag:

```bash
php artisan vendor:publish --tag=rocket-config
```

Publish everything at once:

```bash
php artisan vendor:publish --provider='MaherElGamil\Rocket\RocketServiceProvider'
```

## Environment variables

All keys that read from `env()` can be set without publishing the config:

```env
ROCKET_DEFAULT_PANEL=admin
ROCKET_ROOT_VIEW=rocket::app
ROCKET_JS_ENTRY=vendor/maherelgamil/rocketphp/resources/js/rocket.tsx
ROCKET_BRAND="Acme"
```
