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
- Dashboard route + widgets *(shipped)*
- Custom pages *(shipped)* — `Page` classes discoverable via `Panel::discoverPages()`
- Laravel Gate/Policy integration (`viewAny`, `view`, `create`, `update`, `delete`); nav auto-filtered
- `RenderRocketErrorPages` middleware converts 403 / 404 / 419 / 500 GET responses into Inertia error pages
- Global search *(shipped)* — `⌘K` palette with cross-resource search

### Forms
- `Field` base + abstract schema; `Form::applyAfterSave()` + `Field::afterSave()` hook
- Fields know their owning `Resource` (via `Field::setResource()`) so they can build panel-scoped URLs
- **Fields:** `TextInput` · `Textarea` · `Select` · `Checkbox` · `Radio` · `MultiSelect` · `Toggle` · `DatePicker` · `FileUpload` · `BelongsTo` · `BelongsToMany` · `KeyValue`
- **Layout:** `Section` · `Tabs` (URL-hash persistence, per-tab validation-error dots)
- Enum-driven options on `Select` / `Radio` / `MultiSelect` via `EnumSupport`
- Searchable `BelongsTo` with debounced shadcn combobox + auto-resolution of selected label
- Validation rules serialized to Inertia

### Tables
- `Table` + `Column` base
- **Columns:** `TextColumn` (money/number/date/dateTime/since/limit/prefix/suffix/markdown/copyable) · `BadgeColumn` · `BooleanColumn` · `ImageColumn` · `IconColumn`
- **Filters:** `SelectFilter` · `TernaryFilter` · `TrashedFilter` · `DateRangeFilter`
- `DateRangeFilter` uses shadcn Popover + Calendar with preset sidebar
- `HasLabel`, `HasColor`, `HasIcon` enum contracts
- Search, sort, pagination, per-page selector
- **Row actions:** `ViewAction`, `EditAction`, `DeleteAction` (ability-gated)
- **Row action overflow:** `Table::actionsOverflowAfter(int)` collapses into dropdown menu
- **Bulk actions:** `BulkDeleteAction`

### Relation managers *(shipped)*
- `RelationManager` abstract + `RelationManagerRenderer` (policy-gated per `viewAny`)
- Full namespaced search / sort / filters / pagination per manager (`rm_{name}_*`) so siblings on the same page don't collide
- `Resource::relationManagers()` declares attached managers
- `Resource::relationManagersLayout()` picks `tabs` (default, with record-count badges and URL-hash persistence `#rm=name`) or `stacked`
- `rocket.pagination.relation_manager.per_page` config (default `5`) for smaller nested-table pages
- `EditRecord` / `ViewRecord` render below the form; preserve sibling state on navigation

### Widgets *(shipped)*
- Widgets can be displayed on resource pages (list, create, edit, view)
- `Resource::widgets()` + `Resource::getWidgets($page)` for page-specific rendering
- `CanRenderOnPages` trait with `->only(['list', 'create', 'edit', 'view'])`
- Widgets exposed via `toArray()` and passed to Inertia responses

### Frontend
- Self-contained React entrypoint (`rocket.tsx`) + pages: list, create, edit, view, dashboard, error
- **shadcn components:** badge, button, card, input, label, select, skeleton, switch, table, textarea, popover, calendar, sonner, dropdown-menu
- **Responsive `PanelShell`:** collapsible desktop sidebar + mobile slide-in drawer *(configurable)*
- Flash-to-toast wiring via `useFlashToast` hook
- Semantic palette mapping (`badgeColorClasses`) with `dark:` variants
- Lucide icons rendered dynamically via `lucide-react/dynamic`
- Inertia error page reuses the panel chrome

### Testing
- Pest feature tests across every surface: resources, forms, auth, actions, filters, dashboard, enum support, `Color` tokens, `BelongsTo` (+ AJAX lookup), `BelongsToMany`, `Section`, `Tabs`, `KeyValue`, all column types, all formatters, relation managers (scoping, namespacing, policies, layout, default per_page)
- **99 tests**, all green

---

# Next Phases

Each phase below includes a scope statement, backend / frontend / tests breakdown, and a "done when" acceptance gate. Phases are ordered by user-visible value.

---

## Phase 1 — Global Search (⌘K palette) *(shipped)*

- `Resource::globalSearchColumns(): array` — columns to search; return `[]` to opt out.
- `Resource::globalSearchResult(Model $record): array` — shape: `{ title, description?, url, icon? }`.
- `Resource::getGlobalSearchEloquentQuery(): Builder` — allows scoped searches.
- `GlobalSearchController` at `{panel}/search?q=...` returns JSON. Fan out across resources, filter by `viewAny` policy, per-resource `take(5)`, overall cap `50`.
- Panel config `Panel::globalSearchEnabled(bool)` + `Panel::globalSearchPlaceholder(string)`.

---

## Phase 2 — Theming *(shipped)*

- `Panel::setPrimaryColor()`, `setAccentColor()`, `setFont()`, `setRadius()`, `setDensity()`.
- Serialize tokens into `Panel::toSharedProps()` as `theme`.
- CSS variables injected via `PanelShell` for dynamic theming.
- Density scales `--rocket-gap`, `--rocket-input-height`, `--rocket-font-size`.

---

## Phase 3 — Notifications Center *(shipped)*

- `Panel::notificationsEnabled(bool)` + shared prop `notifications = { enabled, urls }`.
- `NotificationController` with `index`, `recent`, `markRead`, `markAllRead` endpoints.
- Topbar bell icon with count badge.
- Popover showing recent notifications with "mark read" action.

---

## Phase 4 — Widget Library *(shipped)*

- **Backend:** `StatWidget`, `ChartWidget`, `TableWidget`, `RecentRecordsWidget`, `ActivityFeedWidget`.
- **Resource widgets:** `Resource::widgets()` + `CanRenderOnPages` trait with `->only([...])`.
- **Frontend:** Lazy-loaded Recharts for chart widget.
- All widgets support column span, loading skeleton, empty state.

---

## Phase 5 — i18n

**Goal.** Every user-facing string routed through translators; one `lang/vendor/rocket/*.json` file per locale consumers can override.

### Backend
- Audit every `'literal string'` in panel code; replace with `__('rocket::...')`.
- Publish tag `rocket-lang` exposing `lang/vendor/rocket/en.json` (and optionally `ar.json`).
- `Panel::locale(string | Closure)` to pin the panel locale independent of the app default (Closure receives request).

### Frontend
- Strings currently hard-coded in React (`"No records found."`, `"Search..."`, action labels, empty states) come from server as part of schema payloads — most already do via column labels. Pass a `translations` map in `panel.toSharedProps()` for the remaining frontend-only copy.

### Tests
- Snapshot test: load panel under `ar` locale, assert an expected string is translated and not the English fallback.
- Arch test: no hard-coded user-facing English strings remain in the package's React files (regex check over `components/` + `pages/`).

### Done when
- Shipping with English + Arabic out of the box.
- Host-app README has a "Translations" section.

---

## Phase 6 — Import / Export

**Goal.** One-click CSV export on any resource list; queued CSV import with validation + error reporting.

### Backend
- `ExportBulkAction` — default available on every resource with `hasExport()` = true. Streams CSV with the columns from `Table`.
- `ImportAction` — resource header action, opens a modal accepting a file. Dispatches a queued `ImportResourceJob` that parses CSV via league/csv, validates row-by-row against the form's validation rules, and records success/fail counts.
- `ImportReport` model to persist per-row errors; lookup page at `{panel}/imports/{report}`.
- `Resource::importMapping()` — columns → field names. Required.

### Frontend
- Export action is a standard bulk action — reuse existing UI.
- Import modal with file picker + "download sample CSV" button (generated server-side from mapping).
- Post-upload: show progress (queue status via short-polling on the report id).

### Tests
- Roundtrip: seed 50 rows, export, reimport, assert count matches and data is identical.
- Validation failures are captured in the report, not silently dropped.
- Very large CSV (10k rows) exports stream without loading everything into memory — assert peak memory stays below a threshold.

### Done when
- Host app can export Posts and reimport without duplicates.
- Queue worker is required; CI runs with a synchronous queue so tests stay fast.

---

## Phase 7 — Audit Log

**Goal.** Opt-in per-resource change tracking using `spatie/laravel-activitylog`.

### Backend
- `Resource::auditable(): bool` — when true, Rocket records `create`, `update`, `delete` events.
- Relation manager for the resource's own audit log on the edit / view page — scoped to the record.
- "Who / when / what changed" diff viewer (field-level before/after).

### Frontend
- `ActivityTimelineColumn` + `ActivityDiffDialog` components.

### Tests
- Policy: users without `auditLog` permission don't see the relation manager.
- A create + update + delete cycle produces exactly 3 log entries.

### Done when
- Demo resource shows its audit trail under an "Activity" relation manager tab.

---

## Phase 8 — Multi-tenancy (integration surface)

**Goal.** Optional `stancl/tenancy` helpers — we don't own the model, we document the two supported shapes.

### Deliverables
- `Panel::tenancy('stancl' | 'custom')` config flag.
- Middleware wiring example: scoping Rocket panel routes to a tenant.
- Documentation: "Panel-per-tenant" vs. "shared panel, tenant column" patterns.

### Tests
- Two fixture panels (admin + customer) with different models; ensure routes don't leak cross-tenant.

### Done when
- README section with both patterns documented end-to-end.
- Keep the surface area minimal; this is an integration recipe, not a feature.

---

## Release 1.0 prep

- [ ] Publish on Packagist with a smoke-test host app wired to every feature
- [ ] README + CHANGELOG kept in sync with every public-API change
- [ ] SemVer git tags on all releases; document breaking changes
- [ ] CI pipeline: matrix over Laravel 11 / 12 / 13 and PHP 8.2 / 8.3 / 8.4
- [ ] Frontend bundle size audit (current `rocket.js` sits ~815 kB raw / ~238 kB gzip — watch for regressions). Move Recharts (Phase 4) behind a dynamic import to avoid regressing the base bundle.

---

## Recommended order

Phases 1-4 shipped. Remaining:

5. **i18n (Phase 5)** — before 1.0 tag; do it once, all strings in one pass.
6. **Import / export (Phase 6)** — after 1.0 tag; it's additive.
7. **Audit log (Phase 7)** and **multi-tenancy (Phase 8)** — post-1.0 integration surfaces; drive by real consumer demand rather than speculation.

---

## Open design questions

- **Global search.** One shared endpoint that fans out across resources vs. per-resource endpoints behind a palette aggregator. Shared is simpler to consume; per-resource gives finer-grained caching and authorization. **Current lean:** shared endpoint, per-resource `take(5)` cap.
- **Theming surface.** Expose CSS variables (consumer controls via stylesheet), tokens serialized from PHP config, or both? **Current lean:** both — PHP config for the common 80%, CSS vars as the escape hatch.
- **Notifications storage.** Reuse Laravel's native `notifications` table, or introduce `rocket_notifications`? **Current lean:** reuse Laravel's — less surface area, plays nice with existing `Notifiable`.
- **Chart library.** Recharts (React-idiomatic, heavier) vs. Chart.js (lighter, less idiomatic) vs. ECharts (powerful, heaviest). **Current lean:** Recharts, lazy-loaded.
- **Form Tabs vs. Sections.** Keep Tabs as a wrapper over Section children, or add a `TabGroup` primitive supporting mixed tab / accordion per breakpoint? **Defer** until a real use case.
- **Color tokens vs. raw hex.** Keep both paths indefinitely, or deprecate raw hex? **Current lean:** keep both; raw hex is occasionally useful for per-record integrations (tag colors from a DB row).
- **Icon source.** Stay pinned to lucide names, or abstract? **Current lean:** pinned to lucide. Revisit only if a consumer ships their own icon library.
