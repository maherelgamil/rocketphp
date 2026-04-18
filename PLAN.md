# RocketPHP — Roadmap

A Filament-style admin panel framework for **Inertia.js + React**.

- **Package:** `maherelgamil/rocketphp`
- **Namespace:** `MaherElGamil\Rocket`
- **Stack:** Laravel 11–13 · PHP 8.2+ · Inertia v3 · React 19 · Tailwind v4 · Pest 4

---

## Legend

| Symbol | Meaning |
| :----: | :------ |
| ✅     | Shipped |
| 🚧     | In progress |
| 🎯     | Next up (near-term) |
| 🧭     | Planned (mid-term) |
| 💡     | Idea / under discussion |

---

## ✅ Current Status

### Panel & routing
- Panel + `PanelManager` (fluent config, auto-registers routes on register)
- `Resource` abstract: `ListRecords`, `CreateRecord`, `EditRecord`; store / update / destroy
- `ResourceController` + optional row / bulk action routes
- Dashboard route + widgets
- Laravel Gate/Policy integration (`viewAny`, `create`, `update`, `delete`); nav auto-filtered

### Forms
- `Field` base + abstract schema; `Form::applyAfterSave()` + `Field::afterSave()` hook
- **Fields:** `TextInput` · `Textarea` · `Select` · `Checkbox` · `Radio` · `MultiSelect` · `Toggle` · `DatePicker` · `FileUpload` · `BelongsTo` · `BelongsToMany`
- **Layout:** `Section` (labeled, collapsible groups with their own column count)
- Enum-driven options on `Select` / `Radio` / `MultiSelect` via `EnumSupport`
- Validation rules serialized to Inertia

### Tables
- `Table` + `Column` base
- **Columns:** `TextColumn` · `BadgeColumn` · `BooleanColumn` · `ImageColumn`
- **Filters:** `SelectFilter` · `TernaryFilter` · `TrashedFilter` · `DateRangeFilter`
- `DateRangeFilter` uses a **shadcn Popover + Calendar** with preset sidebar (Today, Yesterday, Last 7 / 30 days, This / Last month, This year, Clear)
- `BadgeColumn` + `EnumSupport` + `Color` palette (16 Tailwind tokens, light/dark aware)
- Search, sort, pagination, per-page selector
- **Row actions:** `DeleteAction`, `EditAction` (link-style, ability-gated)
- **Bulk actions:** `BulkDeleteAction`

### Frontend
- Self-contained React entrypoint (`rocket.tsx`) + pages: list, create, edit, dashboard
- **shadcn components:** badge, button, card, input, label, select, skeleton, switch, table, textarea, popover, calendar
- Semantic palette mapping (`badgeColorClasses`) with `dark:` variants
- Bundled via Vite with the `@inertiajs/vite` plugin

### Testing
- Pest feature tests across every surface: resources, forms, auth, actions, filters, dashboard, enum support, `Color` tokens, `BelongsTo` / `BelongsToMany`, `Section`, `ImageColumn`, `BooleanColumn`, `EditAction`, simple fields
- **59+ tests**, all green

---

## 🎯 Near-term

Small, high-leverage fills that close visible UX gaps cheaply.

| # | Feature | Notes |
|---|---------|-------|
| 1 | **`ViewAction`** | Read-only show page; mirrors `EditAction` as a link action pointing at `/{record}/view`. Needs a new show page component. |
| 2 | **Form Tabs** | Tabbed layout alongside `Section` (same tree semantics, tab UX). |
| 3 | **Searchable `BelongsTo`** | AJAX endpoint for option lookup; currently `->searchable()` only toggles a frontend flag. |
| 4 | **`KeyValue` field** | `object<string,string>` editor — useful for meta / config blobs. |
| 5 | **`IconColumn`** | Single lucide icon per row, color tokens like `BooleanColumn`. |
| 6 | **Flash / toast UI** | Wire Laravel session flashes to an Inertia toast stack. |

---

## 🧭 Mid-term

Bigger features that change the shape of the framework.

| # | Feature | Notes |
|---|---------|-------|
| 7 | **Global search** | `⌘K` command palette across resources, policy-filtered. |
| 8 | **Relation managers** | Nested tables on edit pages (e.g. `Post → Comments` table). |
| 9 | **Notifications center** | Panel-wide notification UI + unread count. |
| 10 | **Theming** | CSS vars + design tokens for consumer overrides. |
| 11 | **i18n** | Publish-friendly lang files per panel. |
| 12 | **Multi-tenancy** | Optional `stancl/tenancy` integration helpers. |

---

## 🚀 Release prep

- [ ] Publish on Packagist with a smoke-test host app wired to every feature
- [ ] README + CHANGELOG kept in sync with every public-API change
- [ ] SemVer git tags on all releases; document breaking changes

---

## 🧠 Recommended order

1. Finish the small field / column gaps (**1–6**) first — they close visible UX surface cheaply and leave the API stable.
2. Then go wide with **global search (7)** and **relation managers (8)** — both are bigger but self-contained.
3. Invest in theming / i18n / multi-tenancy (**10–12**) after the framework has real production users with concrete needs.

---

## 💡 Open design questions

- **Searchable AJAX shape.** Per-field endpoint vs. a shared `/admin/_rocket/search?resource=...&query=...` route? Per-field is simpler; shared is more uniform.
- **Form Tabs vs. Sections.** Should Tabs be a separate top-level component, or a display mode on `Section` (`->asTabs()`)? Tab state (open tab) also needs a URL fragment or query param to survive reloads.
- **ViewAction render.** Reuse `record-form` in read-only mode, or build a dedicated `record-view` with a definition-list layout? Read-only form is cheap but looks transactional; definition list reads better but doubles the code.
- **Relation managers.** Should they live as a separate schema entry on `Resource`, or as a special `Section` variant that embeds a full `Table`? Scope of pagination / filter params when nested is the tricky part.
- **Color tokens vs. raw hex.** Keep both paths indefinitely, or eventually deprecate raw hex in favor of an extensible palette?

