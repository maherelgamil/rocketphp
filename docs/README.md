# RocketPHP Documentation

A **Server-Driven UI** framework for Laravel + Inertia.js + React.
The server emits complete UI schemas as JSON; the React client
renders them deterministically. One source of truth, in PHP.

> **New here?** Read [Server-Driven UI](advanced/server-driven-ui.md)
> for the pattern, then walk through the [Quick Start](getting-started/quick-start.md).

---

## Getting Started

- [Installation](getting-started/installation.md) — Composer, service
  provider, Vite wiring, Tailwind source path.
- [Quick Start](getting-started/quick-start.md) — scaffold a panel,
  generate a resource, see the list view in the browser.

## Core Building Blocks

The four primitives you compose every admin screen from:

| Primitive | Docs | What it is |
| --- | --- | --- |
| **Panel** | [Panel Configuration](panels/configuration.md) | The top-level container — routes, brand, middleware, discovery. |
| **Resource** | [Overview](resources/overview.md) · [Custom Pages](resources/custom-pages.md) · [Relation Managers](resources/relation-managers.md) | An Eloquent model bound to CRUD pages, a table, a form, and policies. |
| **Page** | [Overview](pages/overview.md) · [Blocks](pages/blocks.md) · [Actions](pages/actions.md) | The unit of routing. Every screen — dashboard, list, edit, custom — is a `Page` subclass. |
| **Widget** | [Overview](widgets/overview.md) | Dashboard tiles — stats, charts, recent records, activity feeds. |

## Feature Guides

- **Tables** — [Columns](tables/columns.md) · [Filters](tables/filters.md) · [Actions](tables/actions.md)
- **Forms** — [Fields, sections, tabs](forms/fields.md)
- [Authorization](authorization.md) — policy-gated resources, pages, and actions.
- [Notifications](notifications.md) — server-rendered toasts and flash messages.
- [Global Search](global-search.md) — cross-resource search with scorers.
- [Internationalization & RTL](i18n.md) — translation, locale switching, RTL layout.

## Reference

- [Configuration](configuration.md) — every `config/rocket.php` key,
  env variables, and `vendor:publish` tags (`rocket-config`,
  `rocket-views`, `rocket-assets`, `rocket-lang`, `rocket-stubs`).
- **CLI generators** — `rocket:make-panel`, `rocket:make-resource`,
  `rocket:make-page`. Each is documented inline in its feature guide.

## Concepts

- [Server-Driven UI](advanced/server-driven-ui.md) — the pattern, the
  three-move extension mechanism (PHP class → React renderer → feature
  test), and when SDUI is the wrong tool.

## Contributing

- [Contributing guide](contributing.md) — dev setup, test conventions,
  PR expectations.
