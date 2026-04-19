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
- `RenderRocketErrorPages` middleware converts 403 / 404 / 419 / 500 GET responses into Inertia error pages

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

### Frontend
- Self-contained React entrypoint (`rocket.tsx`) + pages: list, create, edit, view, dashboard, error
- **shadcn components:** badge, button, card, input, label, select, skeleton, switch, table, textarea, popover, calendar, sonner, dropdown-menu
- **Responsive `PanelShell`:** collapsible desktop sidebar + mobile slide-in drawer
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

## Phase 1 — Global Search (⌘K palette)

**Goal.** Panel-wide command dialog that searches across all resources the user can `viewAny`, with a single shortcut (`⌘K` / `Ctrl+K`).

### Backend
- `Resource::globalSearchColumns(): array` — columns to search; return `[]` to opt out.
- `Resource::globalSearchResult(Model $record): array` — shape: `{ title, description?, url, icon? }`.
- `Resource::getGlobalSearchEloquentQuery(): Builder` — allows scoped searches (e.g. `with()` eager-loads, soft-delete filtering).
- New `GlobalSearchController` at `{panel}/search?q=...` returning JSON. Fan out across resources, filter by `viewAny` policy, per-resource `take(5)`, overall cap `50`.
- Panel config `Panel::globalSearchEnabled(bool)` + `Panel::globalSearchPlaceholder(string)`.

### Frontend
- New `<GlobalSearchDialog />` using `cmdk` (add to deps) rendered inside `PanelShell`.
- Keyboard shortcut registration — `⌘K` / `Ctrl+K` toggles open. `Esc` closes. `↑↓` navigate, `Enter` visits.
- Debounced (250ms) fetch via `router.visit({ only })` or plain `fetch` to the search endpoint.
- Grouped results per resource with the resource label as section header; each result shows title, optional description, resource icon.
- Empty state, loading spinner, "no results" copy.

### Tests
- `GlobalSearchControllerTest`: unauthenticated → 401/403; authenticated user sees only resources they `viewAny`; query parameter sanitization; per-resource result cap; disabled panel returns 404.
- Opt-out: resource with `globalSearchColumns() = []` never appears in results.
- Scope: custom `getGlobalSearchEloquentQuery` is respected.

### Done when
- Keyboard shortcut + palette work across every host-app resource.
- Returns ≤ 5 results per resource, ≤ 50 total, always within 300ms on a 10k-row table with indexed columns.
- 3+ new Pest tests green.
- No regressions in the 99-test baseline.

---

## Phase 2 — Theming

**Goal.** Consumers can rebrand the panel (colors, radii, density, font) without forking classes or Tailwind config.

### Backend
- `Panel::theme(array $tokens)` accepting a token map — `primary`, `accent`, `radius`, `density` (compact/default/comfortable), `font`.
- Serialize tokens into `Panel::toSharedProps()` as `theme`.

### Frontend
- `ThemeProvider` component mounted by `PanelShell` — injects a `<style>` block setting CSS variables on `:root` (`--rocket-primary`, `--rocket-radius`, etc.).
- Refactor existing shadcn components to read from those variables instead of hard-coded Tailwind tokens where they differ.
- Dark-mode aware: each token takes `{ light, dark }` or a single string (shared).
- Density switch scales `--rocket-gap`, `--rocket-input-height`, `--rocket-font-size` — wire the three most-visible components (table rows, form fields, sidebar nav).

### Tests
- `PanelTest::it_serializes_theme_tokens`.
- Smoke test: rendering the panel with an overridden `primary` exposes the override in the Inertia payload.
- Frontend: Playwright / browser smoke for contrast (optional; defer if painful).

### Done when
- Host app can set a brand color + radius in `AdminPanelProvider` and every panel page picks it up.
- Existing color classes (badge palette) remain the canonical way to pick per-record colors; theming only affects chrome.
- README section: "Theming" with before/after screenshots.

---

## Phase 3 — Notifications Center

**Goal.** Unread-count badge in the topbar; dropdown showing recent notifications; integrates with Laravel's database notifications table.

### Backend
- `rocket_notifications` table migration (wraps Laravel's `notifications` table — reuse if consumer has already published it).
- Panel-level `Panel::notificationsEnabled(bool)` + shared prop `notifications = { unread_count, recent: [...] }` (with `Inertia::optional()` for the recent list — only fetched when the panel asks for it).
- `NotificationController` with `index` (paginated full list), `markRead($id)`, `markAllRead`.
- Support Laravel's `Notifiable` contract — if `$panel->getAuthUser()` implements it, we use it unchanged.

### Frontend
- Topbar bell icon with count badge.
- Popover listing the 10 most recent notifications; each has icon, title, timestamp, "mark read" action.
- "View all" link → full page at `{panel}/notifications`.
- Deferred prop pattern: `unread_count` is eager, `recent` is deferred for fast first paint.
- Real-time nice-to-have: opt-in Echo listener (gated by `rocket.notifications.realtime = true`) wiring to the user's private channel.

### Tests
- `NotificationControllerTest` for index + mark actions; policy check ensures users only see their own notifications.
- Inertia-level: shared-prop `unread_count` is present + correct; `recent` only loads when `only` param requests it.
- Mark-all-read empties the unread set but preserves history.

### Done when
- Host app can dispatch a Laravel `Notification` targeting the panel user, and the bell shows unread count within one Inertia partial refresh.
- Bell dropdown visually polished (spacing, empty state, long-title truncation).
- Full notifications page paginates via `DataTable`.

---

## Phase 4 — Widget Library

**Goal.** Ship four production-quality dashboard widgets that consumers can drop into `Dashboard::widgets()` without writing JSX.

### Backend
- `StatWidget` — big number + delta (`->compareTo(Builder $previousPeriod)`), trend indicator, optional sparkline data.
- `ChartWidget` (line/bar/area) — builds data server-side; uses Recharts on the frontend. API: `->data(Builder | Closure)`, `->interval('day' | 'week' | 'month')`, `->type('line' | 'bar' | 'area')`.
- `RecentRecordsWidget` — wraps a `Table` with a fixed `limit` and a "View all" link to the resource list.
- `ActivityFeedWidget` — renders a vertical timeline from an arbitrary query (or from Spatie's activitylog if present).

### Frontend
- Recharts added to deps (lazy-loaded so dashboard bundle doesn't bloat every page).
- Each widget ships as a distinct React component under `components/widgets/`.
- All widgets support a loading skeleton (Inertia deferred props) and an empty state.

### Tests
- Serialization test per widget type (`toArray()` shape matches schema).
- `ChartWidget` aggregation: feed synthetic data, assert bucketing by day/week/month is correct across DST boundaries.

### Done when
- Dashboard demo in host app uses all four widgets with real models.
- README "Dashboard" section includes a widget cookbook.

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

1. **Global search (Phase 1)** — highest-visibility polish now that relation managers have landed; reuses the searchable `BelongsTo` shape.
2. **Theming (Phase 2)** — unblocks consumers who want to rebrand before shipping to their users.
3. **Notifications (Phase 3)** — pairs well with theming and completes the "panel chrome" feel.
4. **Widget library (Phase 4)** — moves the dashboard from demo to useful.
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
