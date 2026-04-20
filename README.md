# RocketPHP

A **Server-Driven UI (SDUI)** framework for Laravel + Inertia.js + React + shadcn/ui.

```
┌───────────┐    UI Schema (JSON)     ┌───────────┐
│  Laravel  │ ──────────────────────► │   React   │
│   (PHP)   │     "render this"       │  (client) │
└───────────┘                         └───────────┘
```

RocketPHP sends widget definitions, table schemas, form configurations, and
page blocks from PHP. The React layer is *stateless* — it renders whatever it
receives. One source of truth, no duplicated validation, no client-side
API surface to design.

**[→ Read the docs](docs/README.md)**

## Requirements

- PHP 8.2+
- Laravel 11 / 12 / 13
- Inertia.js v3 (`inertiajs/inertia-laravel`)
- Tailwind CSS v4 in the host app
- React 19 + `@inertiajs/react`
- shadcn/ui components

## Install

```bash
composer require maherelgamil/rocketphp
```

Then wire up Tailwind and Vite — see the
[Installation guide](docs/getting-started/installation.md).

## Quick look

```bash
php artisan rocket:make-panel Admin
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
            ->searchable(['name', 'email']);
    }
}
```

Visit `/admin/users` — you get a sortable, searchable, paginated table,
policy-gated and ready to extend. Add a `form()` to unlock create/edit.
Full walkthrough: [Quick Start](docs/getting-started/quick-start.md).

## Features

- **Resources** — auto-generated list, create, edit, view pages
- **Tables** — columns, filters, row + bulk actions, pagination, search
- **Forms** — 15 field types, layout grouping, server-side validation
- **Widgets** — stats, charts, tables, recent records, activity feeds
- **Relation managers** — manage related records inline on edit/view
- **Custom pages** — drop a class in `Pages/`, it's auto-discovered
- **Authorization** — policy-gated at every level (nav, page, action)
- **Global search** — `Cmd+K` across resources
- **Notifications** — bell in the header, backed by `Notification::send()`
- **i18n + RTL** — locale switcher, JSON translations, automatic RTL

Full reference in [docs/](docs/README.md).

## Docs

| | |
| --- | --- |
| [Installation](docs/getting-started/installation.md) | Requirements and wiring |
| [Quick Start](docs/getting-started/quick-start.md) | Your first panel + resource |
| [Panel Configuration](docs/panels/configuration.md) | Every `->` method, default, and behavior |
| [Resources](docs/resources/overview.md) | Model binding, pages, navigation |
| [Tables](docs/tables/columns.md) | Columns, filters, actions |
| [Forms](docs/forms/fields.md) | Field types, validation, layout |
| [Widgets](docs/widgets/overview.md) | Dashboard + resource widgets |
| [Authorization](docs/authorization.md) | Policies and visibility |
| [i18n & RTL](docs/i18n.md) | Locale switcher, translations, logical layout |
| [Configuration](docs/configuration.md) | `config/rocket.php` reference and `vendor:publish` tags |

## Testing

```bash
git clone https://github.com/maherelgamil/rocketphp.git
cd rocketphp
composer install
./vendor/bin/pest
```

## Contributing

PRs welcome — see the [contributing guide](docs/contributing.md).

## License

MIT © Maher El Gamil
