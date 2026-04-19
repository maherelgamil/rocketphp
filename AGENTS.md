# AGENTS.md

This file provides guidance to AI agents (Claude, GPT, Copilot, etc.) when working with code in this repository.

## Overview

**RocketPHP** is a Server-Driven UI (SDUI) framework for Laravel + Inertia.js + React. The server sends complete UI schemas (JSON) describing what to render; the React client renders it deterministically. This replaces traditional client-side state management and API design with a single source of truth in PHP.

- **Package:** `maherelgamil/rocketphp` (Composer) / `@maherelgamil/rocketphp` (NPM)
- **Namespace:** `MaherElGamil\Rocket`
- **Stack:** PHP 8.2+ · Laravel 11–13 · Inertia v3 · React 19 · Tailwind CSS v4 · Pest 4
- **Type:** Laravel service provider package with dual frontend (React) and backend (PHP) components

## Essential Commands

### Testing

```bash
# Run all tests (from package root)
composer test
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/ResourceTest.php

# Run tests matching a pattern
./vendor/bin/pest --filter=testResourceIndex

# Run with compact output
./vendor/bin/pest --compact
```

### Development

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

## Architecture Overview

### Core Pattern: Server-Driven UI

RocketPHP serializes all UI to JSON. The server generates schemas; React renders them deterministically without client-side decision-making.

```
Request → ResourceController → Resource::table()/form() → Inertia Serialization
                                                              ↓
                                                          JSON Schema
                                                              ↓
                                                         React Renderer
                                                              ↓
                                                        Stateless UI
```

### Panel-Based Organization

Each `Panel` scopes:
- **Routes** — auto-registered under `/admin` (or custom path)
- **Resources** — Eloquent models with CRUD pages, tables, forms, authorization
- **Pages** — dynamic content (CRUD + discoverable custom pages)
- **Middleware** — auth, error handling, view rendering

### Directory Structure

```
src/
├── Commands/              # Artisan generators: make-panel, make-resource
├── Dashboard/             # Widget classes: StatWidget, ChartWidget, TableWidget, etc.
├── Facades/               # Rocket:: facade for runtime access
├── Forms/
│   ├── Components/        # Field types: TextInput, Select, BelongsTo, DatePicker, etc.
│   └── Form.php          # Form schema builder
├── Http/
│   ├── Controllers/      # ResourceController, GlobalSearchController, PanelController
│   └── Middleware/       # RenderRocketErrorPages, authorization middleware
├── Pages/
│   ├── Blocks/           # Block types: WidgetBlock, GridBlock, HtmlBlock
│   ├── CreateRecordPage.php
│   ├── EditRecordPage.php
│   ├── ListRecordsPage.php
│   ├── ViewRecordPage.php
│   ├── DashboardPage.php
│   └── ResourcePage.php  # Base for custom page discovery
├── Panel/
│   ├── Panel.php         # Fluent config: path(), brand(), middleware(), etc.
│   ├── PanelManager.php  # Registry (singleton)
│   └── PanelProvider.php # Abstract base for app panel definitions
├── Resources/
│   ├── RelationManagers/ # Manage related records on edit/view pages
│   └── Resource.php      # Abstract base: table(), form(), widgets(), policies
├── Support/
│   ├── Contracts/        # Interfaces: HasColor, HasIcon, HasLabel
│   ├── Enums/            # Density, Font, Color, Grid layout constants
│   ├── Color.php         # Color token utilities
│   └── Traits/           # Reusable concerns
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
│   ├── data-table.tsx   # Paginated, sortable, filterable table
│   ├── record-form.tsx  # Form renderer with validation
│   ├── widget-renderer.tsx  # Widget schema renderer
│   ├── block-renderer.tsx   # Page block renderer
│   └── ui/              # shadcn/ui: button, input, select, etc.
├── lib/
│   ├── types.ts         # TypeScript definitions for all schemas
│   ├── grid.ts          # Grid layout utilities
│   └── utils.ts         # Formatting, color mapping, etc.
└── rocket.tsx           # Entry point for Vite

tests/
├── Feature/             # Integration tests: resources, forms, auth, policies
├── Fixtures/            # Stub models, policies, relation managers
└── TestCase.php        # Base test setup (Orchestra\Testbench)
```

### Key Architectural Components

#### **Panel & PanelManager**
- `Panel` — fluent configuration object for routes, resources, middleware, theme
- `PanelManager` — singleton registry of all panels; auto-discovered or manually registered
- `PanelProvider` — abstract base; host app extends this to define panels

#### **Resource**
- Abstract class; one per Eloquent model (e.g., `UserResource extends Resource`)
- Declares:
  - `$model` — Eloquent class
  - `table()` — columns, filters, search, pagination
  - `form()` — fields, layout, validation
  - `widgets()` — dashboard widgets
  - `relationManagers()` — related-record managers
  - Custom pages via `discoverPages()` in resource subdirectory
- Fully policy-gated (checks `viewAny`, `view`, `create`, `update`, `delete`)

#### **Form & Field**
- `Form` — fluent builder
- `Field` — abstract base; subclasses: `TextInput`, `Select`, `BelongsTo`, `DatePicker`, `FileUpload`, `KeyValue`, etc.
- Fields serialize to JSON with validation rules
- `Section` and `Tabs` for layout

#### **Table & Column**
- `Table` — fluent builder; columns, filters, search, pagination, actions
- `Column` — abstract base; subclasses: `TextColumn`, `BadgeColumn`, `ImageColumn`, `BooleanColumn`, `IconColumn`
- Sorting, filtering, pagination via query params

#### **Controllers & Routes**
- `ResourceController` — handles CRUD; calls Resource methods to build schemas
- Routes auto-registered by `PanelManager` in `routes/rocket.php`
- No custom route definitions required

### Serialization Pattern

**All UI is generated server-side and serialized to JSON:**
- `Table::toArray()` → column schema, filters, actions
- `Form::toArray()` → field schema, layout, validation rules
- `Widget::toArray()` → data, metadata, rendering hints
- Inertia passes JSON to React
- React deterministically renders without client-side logic

### Authorization Pattern

Every Resource enforces policies:
- `viewAny()` → show in nav, access list page
- `view()` → access view page
- `create()` → show create button/page
- `update()` → show edit button/page
- `delete()` → show delete action

If user lacks `viewAny`, resource is hidden from nav automatically.

## Common Development Tasks

### Add a New Resource

```php
// 1. Create the PHP class
php artisan rocket:make-resource Post

// 2. Define model and table
public static string $model = Post::class;

public static function table(Table $table): Table {
    return $table->columns([
        TextColumn::make('id')->sortable(),
        TextColumn::make('title')->sortable(),
        TextColumn::make('created_at')->dateTime(),
    ]);
}

// 3. Define form (enables create/edit)
public static function form(Form $form): Form {
    return $form->fields([
        TextInput::make('title')->required(),
        Textarea::make('body'),
    ]);
}

// 4. Auto-discovered in PanelProvider::discoverResources()
```

### Add a Form Field Type

1. Subclass `Field` in `src/Forms/Components/FieldName.php`
2. Implement `toArray(): array` to serialize schema
3. Create React component in `resources/js/components/form-field-*.tsx`
4. Add test in `tests/Feature/Forms/`

### Add a Table Column Type

1. Subclass `Column` in `src/Tables/Columns/ColumnName.php`
2. Implement formatting logic
3. Create React component in `resources/js/components/table-cell-*.tsx`
4. Add test in `tests/Feature/Tables/`

### Add a Widget

1. Create in `src/Dashboard/MyWidget.php`
2. Implement `toArray()` for serialized data
3. Declare in `Resource::widgets()` with options:
   - `->columnSpan(1-6)` — grid width
   - `->only(['list', 'edit'])` — show on specific pages
4. Create React component in `resources/js/components/widget-*.tsx`

### Run Tests

```bash
# All tests
./vendor/bin/pest

# Specific test
./vendor/bin/pest tests/Feature/ResourceTest.php

# Matching pattern
./vendor/bin/pest --filter=testName
```

## Key Patterns & Principles

1. **Serialization First** — All UI is generated in PHP, serialized to JSON, rendered by React
2. **No Client-Side Logic** — React never makes decisions; it renders what the server sends
3. **Fluent Configuration** — Builders use method chaining (`Panel::path()->brand()->middleware()`)
4. **Policy-Gated** — All data access respects Laravel policies and gates
5. **Discoverable Components** — Resources, pages, relation managers auto-discovered from directories
6. **Type-Safe Throughout** — PHP type hints + TypeScript types + serialized validation rules
7. **Stateless Frontend** — React components are pure renderers; no state management needed

## Important Design Constraints

- **No JavaScript in resource definitions** — All business logic lives in PHP
- **Validation rules are serialized** — React shows inline errors; no re-validation
- **Authorization is server-side** — Policies control visibility and operations
- **Tables/forms are immutable from client** — Forms POST back; tables show data only
- **Dashboard layout is responsive** — `Panel::dashboardColumns(4)` affects widget grid
- **Configuration is publishable** — Host app publishes `config/rocket.php` for customization

## Testing Conventions

- Use `TestCase` base (extends `Orchestra\Testbench\TestCase`)
- Fixtures in `tests/Fixtures/` — stub models, policies, relation managers
- In-memory SQLite database for all tests (configured in `phpunit.xml`)
- Every change requires a corresponding test or test update

Example test structure:
```php
test('UserResource lists expected columns', function () {
    $columns = UserResource::table(Table::make())->getColumns();
    expect($columns)->toHaveCount(3);
    expect($columns[0])->toBeInstanceOf(TextColumn::class);
});
```

## Frontend Integration (Host App)

RocketPHP registers with the host app's build system:

```css
/* In host app's app.css */
@import 'tailwindcss';
@source '../../vendor/maherelgamil/rocketphp/resources/js';
```

```ts
// In host app's vite.config.ts
laravel({
    input: [
        'resources/css/app.css',
        'resources/js/app.tsx',
        'vendor/maherelgamil/rocketphp/resources/js/rocket.tsx',
    ],
}),
resolve: {
    alias: {
        '@rocket': path.resolve(__dirname, 'vendor/maherelgamil/rocketphp/resources/js'),
    },
},
```

In the host app:
```bash
npm run dev    # Watch frontend
npm run build  # Production build
```
