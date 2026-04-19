# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

**RocketPHP** is a Server-Driven UI (SDUI) framework for Laravel + Inertia.js + React. The server sends complete UI schemas (JSON) describing what to render; the React client renders it deterministically. This replaces traditional client-side state management and API design with a single source of truth in PHP.

- **Package:** `maherelgamil/rocketphp` (Composer) / `@maherelgamil/rocketphp` (NPM)
- **Namespace:** `MaherElGamil\Rocket`
- **Stack:** PHP 8.2+ · Laravel 11–13 · Inertia v3 · React 19 · Tailwind CSS v4 · Pest 4
- **Type:** Laravel service provider package with dual frontend (React) and backend (PHP) components

## Commands

### Development & Testing

```bash
# Run all tests
composer test
# or with Pest directly
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/ResourceTest.php

# Run tests matching a pattern
./vendor/bin/pest --filter=testResourceIndex

# Run with compact output (minimal noise)
./vendor/bin/pest --compact
```

### Code Quality

```bash
# Format PHP code (if using Pint — check parent project)
vendor/bin/pint

# List all available Artisan commands (from host app)
php artisan list
```

### Node Dependencies (Frontend Build)

```bash
# Install dependencies
npm install

# Update packages
npm update
```

## Architecture

### High-Level Pattern

RocketPHP implements a **panel-based admin system** where each `Panel` scopes:
- **Routes** — auto-registered under `/admin` (or custom path)
- **Resources** — Eloquent models with CRUD pages, tables, forms, and authorization
- **Pages** — dynamic content (CRUD + custom discoverable pages)
- **Middleware** — panel-specific auth, error handling, view rendering

**Data Flow:**
```
Request → ResourceController → Resource::table()/form() → Inertia::render()
                                                          ↓
                                                     Serialized Schema (JSON)
                                                          ↓
                                                    React Renderer
                                                          ↓
                                                     Stateless UI
```

### Directory Structure

```
src/
├── Commands/              # Artisan generators: make-panel, make-resource
├── Dashboard/             # Widget classes: StatWidget, ChartWidget, etc.
├── Facades/               # Rocket:: facade for runtime access
├── Forms/
│   ├── Components/        # Field types: TextInput, Select, BelongsTo, etc.
│   └── Form.php          # Form schema builder
├── Http/
│   ├── Controllers/      # ResourceController, GlobalSearchController, PanelController
│   └── Middleware/       # RenderRocketErrorPages, RocketMiddleware
├── Pages/
│   ├── Blocks/           # Block types: Widget, Grid, HTML
│   ├── CreateRecordPage.php
│   ├── EditRecordPage.php
│   ├── ListRecordsPage.php
│   ├── ViewRecordPage.php
│   ├── DashboardPage.php
│   └── ResourcePage.php  # Base for custom page discovery
├── Panel/
│   ├── Panel.php         # Fluent config: path(), brand(), middleware(), etc.
│   ├── PanelManager.php  # Registry (singleton, aliased as 'rocket')
│   └── PanelProvider.php # Abstract base for host-app panel definitions
├── Resources/
│   ├── RelationManagers/ # Manage related records on edit/view pages
│   └── Resource.php      # Abstract base: table(), form(), widgets(), policy checks
├── Support/
│   ├── Contracts/        # Enums: HasColor, HasIcon, HasLabel; Color, Font, Density
│   ├── Enums/            # Grid, layout, density, color constants
│   ├── Color.php         # Color token utilities
│   └── ...              # Shared utilities
└── Tables/
    ├── Actions/          # Row/bulk actions: EditAction, DeleteAction, etc.
    ├── Columns/          # Column types: TextColumn, BadgeColumn, ImageColumn, etc.
    ├── Filters/          # Filters: SelectFilter, DateRangeFilter, TrashedFilter
    └── Table.php        # Table schema builder

resources/js/
├── pages/
│   ├── page.tsx         # List, Create, Edit, View pages (route-driven)
│   ├── dashboard.tsx    # Dashboard with widget grid
│   └── error.tsx        # Error page
├── components/
│   ├── panel-shell.tsx  # Responsive sidebar + header layout
│   ├── data-table.tsx   # Paginated, sortable, filterable table renderer
│   ├── record-form.tsx  # Form renderer with validation error display
│   ├── widget-renderer.tsx  # Widget schema renderer
│   ├── block-renderer.tsx   # Page block renderer
│   └── ui/              # shadcn/ui: button, input, select, etc.
├── lib/
│   ├── types.ts         # TypeScript type definitions for schemas
│   └── utils.ts         # Formatting, color mapping, utilities
└── rocket.tsx           # Entry point (imported in host app's vite.config.ts)

tests/
├── Feature/             # Integration tests: resources, forms, auth, policies
├── Fixtures/            # Stub models, policies, relation managers for tests
└── TestCase.php        # Base test setup (extends Orchestra\Testbench)
```

### Key Classes & Concepts

#### **Panel & PanelManager**
- `Panel` — fluent configuration object; stores routes, resources, middleware, theme
- `PanelManager` — singleton registry of all panels; auto-discovered from config or manual registration
- `PanelProvider` — abstract base; host app extends to define a panel in `bootstrap/providers.php`

#### **Resource**
- Abstract class; subclass per Eloquent model (e.g., `UserResource extends Resource`)
- Declares:
  - `$model` — Eloquent class
  - `table()` — column definitions, filters, search, pagination
  - `form()` — field definitions, layout
  - `widgets()` — dashboard widgets for the resource pages
  - `relationManagers()` — related-record managers for edit/view
  - Custom pages via `discoverPages()` in a resource subdirectory
- Policy-gated: checks `viewAny`, `view`, `create`, `update`, `delete` on each operation

#### **Form & Field**
- `Form` — fluent builder for field schema
- `Field` — abstract base; subclasses: `TextInput`, `Select`, `BelongsTo`, `DatePicker`, etc.
- Fields serialize to JSON with validation rules; React renders them
- `Section` and `Tabs` for layout grouping

#### **Table & Column**
- `Table` — fluent builder; declares columns, filters, search, pagination, actions
- `Column` — abstract base; subclasses: `TextColumn`, `BadgeColumn`, `ImageColumn`, etc.
- Serialized to JSON; React renderer handles sorting, filtering, pagination via query params

#### **Controllers & Routes**
- `ResourceController` — handles CRUD operations; calls Resource methods to serialize schemas
- Routes auto-registered by `PanelManager::registerRoutes()` in `routes/rocket.php`
- No custom route definitions needed; panel path + resource slug = route

### Serialization Pattern

**All UI is serialized to JSON:**
- `Table::toArray()` → column schema, filters, actions
- `Form::toArray()` → field schema, layout, validation rules
- `Widget::toArray()` → data, metadata, rendering hints
- Laravel Inertia passes this JSON to React
- React deterministically renders from schema with no client-side logic

### Authorization Pattern

Every Resource checks policies:
- `viewAny()` → show in nav, show list page
- `view()` → show view page
- `create()` → show create page + button
- `update()` → show edit page + button
- `delete()` → show delete action (row & bulk)

If user lacks `viewAny`, resource is hidden from nav automatically.

### Testing Conventions

- Use `TestCase` base (extends `Orchestra\Testbench\TestCase`)
- Fixtures in `tests/Fixtures/` — stub models, policies, relation managers
- Tests use an in-memory SQLite database (configured in `phpunit.xml`)
- Example pattern:
  ```php
  test('UserResource lists columns', function () {
      $columns = UserResource::table(Table::make())->getColumns();
      expect($columns)->toHaveCount(3);
  });
  ```
- Run: `./vendor/bin/pest --filter=testName`

## Common Tasks

### Add a New Resource

```php
// 1. Create the PHP class
php artisan rocket:make-resource Post

// 2. Define the model binding and table
public static string $model = Post::class;

public static function table(Table $table): Table {
    return $table->columns([
        TextColumn::make('id')->sortable(),
        TextColumn::make('title')->sortable(),
    ]);
}

// 3. Register in PanelProvider::discoverResources()
```

### Add a Form Field

- Subclass `Field` in `src/Forms/Components/`
- Implement `toArray(): array` to serialize schema
- React component in `resources/js/components/form-field-*`
- Add test in `tests/Feature/Forms/`

### Add a Table Column Type

- Subclass `Column` in `src/Tables/Columns/`
- Implement formatting logic in `format(Model $record)` or `getFormattedValue()`
- React component in `resources/js/components/table-cell-*`
- Add test in `tests/Feature/Tables/`

### Add a Widget

- Subclass in `src/Dashboard/` (e.g., `MyWidget extends Widget`)
- Implement `toArray()` to return serialized data
- Declare in `Resource::widgets()` with `->columnSpan(1-6)` and `->only(['list', 'edit'])`
- React component in `resources/js/components/widget-*`

### Run Frontend Build (Host App)

RocketPHP registers:
- CSS import path: `@source '../../vendor/maherelgamil/rocketphp/resources/js'` in Tailwind
- Vite entry: `vendor/maherelgamil/rocketphp/resources/js/rocket.tsx`

In the host app:
```bash
npm run dev    # Watch frontend
npm run build  # Production build
```

## Key Patterns

1. **Fluent Configuration** — All builders use method chaining (`Panel::path('admin')->brand('App')`)
2. **Serialization Over Logic** — React never makes decisions; all logic is in PHP
3. **Policy-Gated** — All data access respects `Gate::authorize()` or policies
4. **Discoverable** — Resources, pages, and relation managers auto-discovered from directories
5. **Stateless Frontend** — React components receive a schema and render it deterministically
6. **Type-Safe** — PHP type hints + TypeScript types + validation rules serialized to frontend

## Important Notes

- **No JavaScript in resource definitions** — All business logic lives in PHP
- **Validation rules are serialized** — React shows inline errors without re-validating
- **Policies are required** — Authorization happens server-side; gates control visibility
- **Tables/forms are immutable from React** — No client-side editing; forms POST back to server
- **Dashboard columns are responsive** — `Panel::dashboardColumns(4)` affects widget grid layout
- **Config is publishable** — Host app publishes `config/rocket.php` to customize brand, colors, density
