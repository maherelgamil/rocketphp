# RocketPHP — Roadmap

A Filament-style admin panel framework for **Inertia.js + React**.

- **Package:** `maherelgamil/rocketphp`
- **Namespace:** `MaherElGamil\Rocket`
- **Stack:** Laravel 11–13 · PHP 8.2+ · Inertia v3 · React 19 · Tailwind v4 · Pest 4

---

## Legend

| Symbol | Meaning |
| :----: | :------ |
| [x]    | Shipped |
| [~]    | In progress |
| [>]    | Next up (near-term) |
| [ ]    | Planned (mid-term) |
| [?]    | Idea / under discussion |

---

## Current Status

### Panel & routing
- Panel + `PanelManager` (fluent config, auto-registers routes on register)
- `Resource` abstract: `ListRecords`, `CreateRecord`, `EditRecord`, `ViewRecord`; store / update / destroy
- `ResourceController` + optional row / bulk action routes
- Panel-scoped AJAX lookup route (`{resource}/lookup/{field}`) for searchable relation fields
- Dashboard route + widgets
- Laravel Gate/Policy integration (`viewAny`, `view`, `create`, `update`, `delete`); nav auto-filtered
- `RenderRocketErrorPages` middleware converts 403 / 404 / 419 / 500 GET responses into Inertia error pages; non-GET responses pass through so form submits still receive plain error codes

### Forms
- `Field` base + abstract schema; `Form::applyAfterSave()` + `Field::afterSave()` hook
- Fields know their owning `Resource` (via `Field::setResource()`) so they can build panel-scoped URLs (used by searchable `BelongsTo`)
- **Fields:** `TextInput` · `Textarea` · `Select` · `Checkbox` · `Radio` · `MultiSelect` · `Toggle` · `DatePicker` · `FileUpload` · `BelongsTo` · `BelongsToMany` · `KeyValue`
- **Layout:** `Section` (labeled, collapsible groups with their own column count) · `Tabs` (contains `Section` children; renders as a tab bar with URL-hash persistence and per-tab validation-error dots)
- Enum-driven options on `Select` / `Radio` / `MultiSelect` via `EnumSupport`
- Searchable `BelongsTo`: `->searchable()` + optional `->searchColumns([...])` / `->lookupLimit(n)` / `->modifyQuery(...)`; frontend renders a debounced shadcn combobox with auto-resolution of the selected label via `?id=X`
- `KeyValue` stores assoc arrays; frontend renders a key/value row editor with add / remove; normalizes JSON strings and empty keys
- Validation rules serialized to Inertia

### Tables
- `Table` + `Column` base
- **Columns:** `TextColumn` (with `->money()`, `->number()`, `->date()`, `->dateTime()`, `->since()`, `->limit()`, `->prefix()`, `->suffix()`, `->markdown()`, `->copyable()`) · `BadgeColumn` · `BooleanColumn` · `ImageColumn` · `IconColumn`
- **Filters:** `SelectFilter` · `TernaryFilter` · `TrashedFilter` · `DateRangeFilter`
- `DateRangeFilter` uses a shadcn Popover + Calendar with preset sidebar (Today, Yesterday, Last 7 / 30 days, This / Last month, This year, Clear)
- `BadgeColumn` + `IconColumn` + `EnumSupport` + `Color` palette (16 Tailwind tokens, light/dark aware)
- `HasLabel`, `HasColor`, `HasIcon` enum contracts
- Markdown columns render sanitized HTML (`html_input => escape`, `allow_unsafe_links => false`) inside a `prose` wrapper
- Search, sort, pagination, per-page selector
- **Row actions:** `ViewAction`, `EditAction`, `DeleteAction` (link-style + ability-gated)
- **Row action overflow:** `Table::actionsOverflowAfter(int)` collapses actions past the threshold into a `MoreHorizontal` dropdown menu (shadcn + Radix); destructive items keep their confirm-dialog flow
- **Bulk actions:** `BulkDeleteAction`

### Frontend
- Self-contained React entrypoint (`rocket.tsx`) + pages: list, create, edit, view, dashboard, error
- **shadcn components:** badge, button, card, input, label, select, skeleton, switch, table, textarea, popover, calendar, sonner, dropdown-menu
- **Responsive `PanelShell`:** collapsible desktop sidebar (icons-only when collapsed; state persisted in `localStorage` under `rocket:sidebar-collapsed`) + mobile slide-in drawer with backdrop and a compact topbar
- Flash-to-toast wiring via `useFlashToast` hook mounted in `PanelShell`
- Semantic palette mapping (`badgeColorClasses`) with `dark:` variants
- Lucide icons rendered dynamically via `lucide-react/dynamic`
- Inertia error page (`rocket/Error`) reuses the panel chrome with a status-aware glyph and a "Back to dashboard" CTA
- Bundled via Vite with the `@inertiajs/vite` plugin

### Testing
- Pest feature tests across every surface: resources, forms, auth, actions, filters, dashboard, enum support, `Color` tokens, `BelongsTo` (including AJAX lookup), `BelongsToMany`, `Section`, `Tabs`, `KeyValue`, `ImageColumn`, `BooleanColumn`, `IconColumn`, `TextColumn` formatters (money/number/date/since/limit/prefix/suffix/markdown), `ViewAction`, `EditAction`, row action overflow, error pages, simple fields
- **89+ tests**, all green

---

## Near-term

All prior near-term items have shipped. Candidates for the next small-scope pass:

| # | Feature | Notes |
|---|---------|-------|
| 1 | **Relation managers (nested tables)** | Render a full `Table` on edit / view pages (e.g. `Post → Comments`). New schema node; isolated pagination + filter state per manager. |
| 2 | **Global search (⌘K palette)** | Panel-wide command dialog across all resources, policy-filtered. Needs a shared lookup endpoint and a per-resource `globalSearch()` hook. |
| 3 | **Notifications center** | Panel-wide unread count + notification UI, backed by Laravel's notifications table. |
| 4 | **Theming** | CSS variables + design tokens so consumers can override palette, radii, and density without forking classes. |

---

## Mid-term

Bigger features that change the shape of the framework.

| # | Feature | Notes |
|---|---------|-------|
| 5 | **i18n** | Publish-friendly lang files per panel; every user-facing string (actions, empty states, validation) routed through translators. |
| 6 | **Multi-tenancy** | Optional `stancl/tenancy` integration helpers; panel-per-tenant or shared-panel-multi-tenant models. |
| 7 | **Widget library** | Common dashboard widgets (stats card, chart, recent records, activity feed) that plug into `Dashboard::widgets()`. |
| 8 | **Import / export** | CSV / Excel round-trip on resources; queued bulk actions. |
| 9 | **Audit log** | Opt-in per-resource change tracking using `spatie/laravel-activitylog` or a bundled alternative. |

---

## Release prep

- [ ] Publish on Packagist with a smoke-test host app wired to every feature
- [ ] README + CHANGELOG kept in sync with every public-API change
- [ ] SemVer git tags on all releases; document breaking changes
- [ ] CI pipeline: matrix over Laravel 11 / 12 / 13 and PHP 8.2 / 8.3 / 8.4
- [ ] Frontend bundle size audit (current `rocket.js` sits ~810 kB raw / ~237 kB gzip — watch for regressions)

---

## Recommended order

1. **Relation managers (1)** — the one feature every Filament-equivalent user asks for; unblocks real admin workloads where parent/child tables live together.
2. **Global search (2)** — highest-visibility polish after relation managers land; reuses the searchable `BelongsTo` shape.
3. **Theming (4)** and **notifications (3)** can move in parallel once the core feature surface is stable.
4. **i18n (5)** before 1.0 tag; do it once, all strings in one pass, rather than retrofitting piecemeal.
5. **Multi-tenancy (6)** after 1.0 — it's an integration surface, not a core concern.

---

## Open design questions

- **Relation managers.** Separate schema entry on `Resource` (`relationManagers()`) or a `Section` variant that embeds a full `Table`? Pagination / filter URL params need namespacing so parent and children don't collide.
- **Global search.** One shared endpoint that fans out across resources vs. per-resource endpoints behind a palette aggregator. Shared is simpler to consume; per-resource gives finer-grained caching and authorization.
- **Form Tabs vs. Sections.** Current design keeps Tabs as a wrapper over Section children. Worth revisiting if we add a `TabGroup` primitive that supports mixed tab / accordion per breakpoint.
- **Color tokens vs. raw hex.** Keep both paths indefinitely, or deprecate raw hex in favor of an extensible palette?
- **Icon source.** Stay pinned to lucide names, or abstract so consumers can swap icon libraries? Pinning is simpler; abstraction defers a hard decision.
- **Theming surface.** Expose CSS variables (consumer controls via stylesheet), tokens serialized from PHP config, or both? Tokens from PHP are more opinionated but let the package ship sensible defaults centrally.
- **Error page status coverage.** Currently 403 / 404 / 419 / 500. Should we also intercept 422 (validation) for non-Inertia requests? Probably not — those are handled natively by the form flow.
