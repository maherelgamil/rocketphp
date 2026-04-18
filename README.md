# RocketPHP

A declarative admin panel framework for Inertia.js + React.

RocketPHP lets you declare admin panels in PHP (panels, resources, tables,
forms) and renders them as Inertia pages using a self-contained React layer
built on shadcn/ui + Tailwind.

> **Status:** early preview. **CRUD** (list, create, edit, delete) and form
> fields (including file upload) are supported. Row/bulk actions, table filters,
> policies, dashboard, global search, and relation managers are still evolving.

## Requirements

- PHP 8.2+
- Laravel 11 / 12 / 13
- Inertia.js v3 (`inertiajs/inertia-laravel`)
- Tailwind CSS v4 in the host app
- React 19 + `@inertiajs/react`

## Installation

```bash
composer require maherelgamil/rocketphp
```

Register the service provider (auto-discovered) and add Rocket's source to
your Tailwind `app.css`:

```css
@import 'tailwindcss';
@source '../../vendor/maherelgamil/rocketphp/resources/js';
```

Add the Rocket entry to your `vite.config.ts`:

```ts
import path from 'node:path';

laravel({
    input: [
        'resources/css/app.css',
        'resources/js/app.tsx',
        'vendor/maherelgamil/rocketphp/resources/js/rocket.tsx',
    ],
}),
// ...
resolve: {
    alias: {
        '@rocket': path.resolve(__dirname, 'vendor/maherelgamil/rocketphp/resources/js'),
    },
},
```

## Quick start

### 1. Create a panel

```bash
php artisan rocket:make-panel Admin
```

This generates `app/Providers/Rocket/AdminPanelProvider.php`:

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
            ->brand('My App')
            ->discoverResources(
                in: app_path('Rocket/Resources'),
                for: 'App\\Rocket\\Resources',
            );
    }
}
```

Register it in `bootstrap/providers.php`:

```php
return [
    // ...
    App\Providers\Rocket\AdminPanelProvider::class,
];
```

### 2. Create a resource

```bash
php artisan rocket:make-resource User
```

```php
use App\Models\User;
use MaherElGamil\Rocket\Resources\Resource;
use MaherElGamil\Rocket\Tables\Columns\TextColumn;
use MaherElGamil\Rocket\Tables\Table;

final class UserResource extends Resource
{
    protected static string $model = User::class;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->sortable(),
                TextColumn::make('email')->sortable()->copyable(),
            ])
            ->searchable(['name', 'email'])
            ->defaultSort('id', 'desc');
    }
}
```

Visit `/admin/users` — you'll get a sortable, searchable, paginated table.
Define `form()` on the resource to enable create/edit and the **New** button.

### Authorization

Register Laravel **policies** for your models. Rocket checks `viewAny`, `create`,
`update`, and `delete` (and related routes) against the resource model. Users
who cannot `viewAny` do not see the resource in the sidebar.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=rocket-config
```

See `config/rocket.php` for available options: default panel, root view,
pagination bounds, default route middleware, brand.

## Testing

```bash
cd vendor/maherelgamil/rocketphp
composer install
./vendor/bin/pest
```

## Roadmap

Shipped in package development: forms, create/edit pages, file upload,
table search/sort/pagination, optional panel **dashboard** with widgets,
row/bulk delete actions (with confirmations), table **filters** (select,
ternary, date range, trashed), `copyable` columns, nav icons, per-page size.

Still open / future: **global search** (e.g. Cmd+K), **relation managers**,
richer notifications/toasts, i18n, multi-tenancy helpers.

## License

MIT © Maher El Gamil
