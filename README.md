# RocketPHP

A **Server-Driven UI (SDUI)** framework for Laravel + Inertia.js + React + shadcn/ui.

## What is SDUI?

**Server-Driven UI** is an architectural pattern where the server controls the UI structure — not just data, but *what components to render, their layout, and behavior*. Instead of the client querying APIs and building UI, the server sends a complete UI schema (JSON) that the client renders deterministically.

```
┌─────────────┐    UI Schema (JSON)    ┌─────────────┐
│   Laravel  │ ─────────────────────► │    React   │
│   (PHP)   │   "render this"       │  (client)  │
└─────────────┘                     └─────────────┘
```

RocketPHP sends widget definitions, table schemas, form configurations, and page blocks from PHP. The React layer is *stateless* — it simply renders what it receives.

### Why SDUI?

- **Single source of truth** — UI logic lives in PHP, not duplicated across frontend
- **Rapid iteration** — change a definition, instantly reflected everywhere
- **No client-side API bloat** — no fetching, no state management, no custom UI code
- **Consistency** — every rendered table/form follows the exact same patterns

## Requirements

- PHP 8.2+
- Laravel 11 / 12 / 13
- Inertia.js v3 (`inertiajs/inertia-laravel`)
- Tailwind CSS v4 in the host app
- React 19 + `@inertiajs/react`
- shadcn/ui v1

## Installation

```bash
composer require maherelgamil/rocketphp
```

Register the service provider (auto-discovered) and add Rocket's source to your Tailwind `app.css`:

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

## Features

### Panel Configuration

```php
$panel
    ->path('admin')
    ->brand('My App')
    ->domain('admin.example.com')           // Optional domain
    ->middleware(['web', 'auth'])           // Route middleware
    ->authMiddleware(['auth', 'verified'])  // Authenticated middleware
    ->guard('web')                          // Auth guard
    ->dashboardColumns(4)                  // Grid columns (1-6)
    ->globalSearchEnabled(true)
    ->globalSearchPlaceholder('Search...')
    ->notificationsEnabled(true)
    ->setPrimaryColor('#3b82f6')
    ->setAccentColor('#8b5cf6')
    ->setFont('Inter')
    ->setRadius('0.5rem')
    ->setDensity('default');                // default | compact | extra-compact
```

### Resources

- **Model binding** — Automatic Eloquent model binding
- **Slug customization** — Custom URL slugs
- **Navigation** — Icon, group, and sort order
- **CRUD pages** — List, Create, Edit, View pages auto-generated
- **Custom pages** — Discover custom pages in `app/Rocket/Resources/{Resource}/Pages/`
- **Relation managers** — Manage related records (HasMany, BelongsToMany, etc.)
- **Global search** — Search across resources (Cmd+K)

### Table

- **Columns**: Text, Boolean, Icon, Image, Badge
- **Sorting** — Column sorting
- **Search** — Global search across defined columns
- **Pagination** — Configurable per-page (10, 25, 50, 100)
- **Filters**: Select, Ternary, Date Range, Trashed
- **Row actions**: Edit, View, Delete (with confirmation)
- **Bulk actions**: Bulk delete (with confirmation)

### Form Fields

- TextInput, Textarea
- Select, MultiSelect
- Checkbox, Radio, Toggle
- DatePicker
- BelongsTo (dropdown)
- BelongsToMany (multi-select)
- FileUpload (with preview)
- KeyValue (dynamic key-value pairs)
- Section (field grouping)
- Tabs (tabbed forms)

### Dashboard Widgets

- **Stat** — Single value with label
- **Chart** — Line, Bar, Area charts
- **Table** — Data table widget
- **Recent Records** — Latest records from a resource
- **Activity Feed** — Activity timeline

### Page Blocks (Custom Pages)

- **Widget** — Embed dashboard widgets
- **Grid** — Multi-column layouts
- **HTML** — Raw HTML content

### Authorization

Register Laravel **policies** for your models. Rocket checks:
- `viewAny` — Show in navigation
- `view` — View record page
- `create` — Create new record
- `update` — Edit record
- `delete` — Delete record

Users who cannot `viewAny` don't see the resource in the sidebar.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=rocket-config
```

See `config/rocket.php` for available options.

## Architecture

```
src/
├── Commands/              # Artisan commands (rocket:make-*)
├── Dashboard/             # Widget classes
├── Facades/               # Rocket facade
├── Forms/Components/      # Form field components
├── Http/Controllers/     # Inertia controllers
├── Http/Middleware/      # Request handling
├── Pages/                 # Page classes (CRUD + custom)
├── Pages/Blocks/          # Block types
├── Panel/                 # Panel & PanelProvider
├── Resources/             # Resource & RelationManager
├── Support/              # Contracts, Enums
└── Tables/               # Table, Columns, Filters, Actions
```

```
resources/js/
├── components/
│   ├── block-renderer.tsx
│   ├── widget-renderer.tsx
│   ├── data-table.tsx
│   ├── record-form.tsx
│   └── ui/               # shadcn/ui components
├── lib/
│   ├── types.ts          # TypeScript types
│   └── utils.ts
└── pages/
    ├── page.tsx
    └── dashboard.tsx
```

## Testing

```bash
cd vendor/maherelgamil/rocketphp
composer install
./vendor/bin/pest
```

## License

MIT © Maher El Gamil
