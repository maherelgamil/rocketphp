# Artisan Commands

Rocket ships two generators. Both are registered automatically — no setup
needed.

## `rocket:make-panel`

```bash
php artisan rocket:make-panel {name}
```

Generates `app/Providers/Rocket/{Name}PanelProvider.php` pre-wired to the
`app/Rocket/Resources/` discovery path.

**Example:**

```bash
php artisan rocket:make-panel Admin
```

Creates `AdminPanelProvider` with `path('admin')` and `brand('admin')`.
Next step: register it in `bootstrap/providers.php`:

```php
App\Providers\Rocket\AdminPanelProvider::class,
```

The `PanelProvider` suffix is appended automatically if omitted — all
three of these generate the same file:

```bash
php artisan rocket:make-panel Admin
php artisan rocket:make-panel AdminPanel
php artisan rocket:make-panel AdminPanelProvider
```

## `rocket:make-resource`

```bash
php artisan rocket:make-resource {name} [--model=]
```

Generates `app/Rocket/Resources/{Name}Resource.php` bound to
`App\Models\{Name}` by default.

**Examples:**

```bash
# Assumes App\Models\Post
php artisan rocket:make-resource Post

# Custom model namespace
php artisan rocket:make-resource Invoice --model='Domain\Billing\Invoice'

# Name already has the Resource suffix
php artisan rocket:make-resource PostResource
```

The stub includes an `id` column and `searchable(['id'])` as a starting
point. Add columns, filters, and a `form()` method to flesh it out —
see [Resources](resources/overview.md).

## `vendor:publish` tags

Rocket publishes several resources under separate tags so you can pick
what you need.

| Tag | Destination | Use |
| --- | --- | --- |
| `rocket-config` | `config/rocket.php` | Change defaults (pagination, brand, routes). |
| `rocket-lang` | `lang/vendor/rocket/{locale}.json` | Override translations. |
| `rocket-views` | `resources/views/vendor/rocket/` | Override the Blade root layout. |
| `rocket-assets` | `resources/js/vendor/rocketphp/` | Fork the React frontend. |

Publish one:

```bash
php artisan vendor:publish --tag=rocket-config
```

Publish all at once:

```bash
php artisan vendor:publish --provider='MaherElGamil\Rocket\RocketServiceProvider'
```
