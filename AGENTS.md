<rocketphp-guidelines>
=== foundation rules ===

# RocketPHP Guidelines

RocketPHP is a Server-Driven UI (SDUI) framework for Laravel + Inertia.js + React.
The server sends UI schema (JSON), React renders deterministically.

## Context

- php - 8.2+
- laravel/framework - v11+
- inertiajs/inertia-laravel - v3
- @inertiajs/react - v3
- react - 19
- shadcn/ui - v1 (UI components)

## Structure

```
rocketphp/src/
├── Commands/           # Artisan commands
├── Dashboard/         # Widget classes (StatWidget, ChartWidget, etc.)
├── Facades/           # Rocket facade
├── Forms/Components/ # Form field components
├── Http/Controllers/  # Inertia controllers
├── Http/Middleware/  # Request handling
├── Pages/             # Page classes (ListRecordsPage, CreateRecordPage, etc.)
├── Pages/Blocks/       # Block types (WidgetBlock, GridBlock, HtmlBlock)
├── Panel/             # Panel & PanelProvider
├── Resources/         # Resource & RelationManager
├── Support/          # Contracts, Enums, Traits
└── Tables/           # Table, Columns, Filters, Actions
```

```
rocketphp/resources/js/
├── components/       # React components
│   ├── block-renderer.tsx
│   ├── widget-renderer.tsx
│   ├── widget-card.tsx
│   ├── data-table.tsx
│   ├── record-form.tsx
│   └── ui/            # shadcn/ui components (Button, Card, Input, etc.)
├── lib/
│   ├── types.ts      # TypeScript types ( Block, Widget, etc.)
│   ├── grid.ts       # Grid utilities
│   └── utils.ts     # General utilities
└── pages/
    ├── page.tsx     # Generic page
    └── dashboard.tsx
```

== Skills Activation

- `tailwindcss-development` — For writing or fixing Tailwind utility classes in JSX/TSX files
- `inertia-react-development` — For Inertia React pages, forms, navigation
- `pest-testing` — For testing PHP code in this package

=== conventions ===

# PHP Conventions

- Use PHP 8 constructor property promotion
- Use explicit return types on all methods
- Follow Laravel naming conventions (camelCase methods, PascalCase classes)
- Use traits for reusable concerns in `Support/Concerns/`

# React Conventions

- Types go in `lib/types.ts` — single source of truth
- Renderers are functions named `renderXxx()` in components/
- Use `import type` for type-only imports
- Keep pages minimal — delegate to renderers

# File Organization

- PHP classes: `src/{Domain}/{Class}.php`
- React components: `resources/js/components/`
- Renderer functions: `resources/js/components/*-renderer.tsx`
- Shared types: `resources/js/lib/types.ts`

=== verification ===

# TypeScript

Run from project root:
```bash
npm run types:check
```

# Linting

Run from project root:
```bash
npm run lint:check
```

# PHP Tests

```bash
cd packages/rocketphp
composer install
./vendor/bin/pest
```

=== test enforcement ===

Every change must be tested. Write a new test or update an existing one.

</rocketphp-guidelines>