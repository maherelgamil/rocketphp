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
- Custom pages *(shipped)* — `Page` classes discoverable via `Panel::discoverPages()`; `rocket:make-page` generator alongside `rocket:make-panel` / `rocket:make-resource`
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
- **shadcn components:** badge, button, card, input, label, select, skeleton, switch, table, textarea, popover, calendar, sonner, dropdown-menu, sheet, sidebar
- **Responsive `PanelShell`:** built on shadcn `sidebar` + `sheet` primitives — collapsible desktop sidebar + mobile slide-in drawer *(configurable)*
- Flash-to-toast wiring via `useFlashToast` hook
- Semantic palette mapping (`badgeColorClasses`) with `dark:` variants
- Lucide icons rendered dynamically via `lucide-react/dynamic`
- Inertia error page reuses the panel chrome

### Testing
- Pest feature tests across every surface: resources, forms, auth, actions, filters, dashboard, enum support, `Color` tokens, `BelongsTo` (+ AJAX lookup), `BelongsToMany`, `Section`, `Tabs`, `KeyValue`, all column types, all formatters, relation managers (scoping, namespacing, policies, layout, default per_page)
- **231 tests / 526 assertions**, all green

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

## Phase 5 — i18n *(shipped)*

- Full `Panel::locale(string | Closure)` API for pinning panel locale independent of app default.
- Publish tag `rocket-lang` exposing `lang/vendor/rocket/en.json` + `ar.json`.
- Frontend translations map serialized via `panel.toSharedProps()` for React-only copy; server-driven labels flow through schema payloads.
- RTL-aware `PanelShell` layout shipped alongside shadcn sidebar migration.

---

## Phase 6 — Import / Export *(shipped, CSV only)*

Filament-style `Exporter` / `Importer` classes with per-column DSLs, queued via `Illuminate\Bus\Batch` for bounded peak memory.

### Exports
- `Exporter` abstract + `ExportColumn::make('id')->label()->formatStateUsing()->counts()->money()->prefix()->suffix()`.
- `Resource::exporter(): ?class-string<Exporter>` hook.
- `ExportBulkAction::make(Exporter::class)` — exports selected rows (uses bulk-actions route).
- `ExportAction::make(Exporter::class)` — new `HeaderAction` primitive, exports full filtered query.
- `Table::headerActions([...])` + `POST {panel}/{resource}/header-actions/{action}` route.
- `PrepareExportJob` → plans chunks via ID ranges (avoids loading full resultset) → fans out `ExportCsvChunkJob[]` in a `Bus::batch()` → `.finally(CompleteExportJob)` concatenates chunks + cleans up.
- `ExportCsvChunkJob` uses `Builder::cursor()` + `fputcsv` line-by-line for bounded memory.
- Download endpoint: `GET {panel}/exports/{id}/download`, user-scoped.
- `ExportReadyNotification` dispatched on completion to the export initiator (Laravel `Notifiable`).
- `rocket:make-exporter {name} --model=`.

### Imports
- `Importer` abstract + `ImportColumn::make('sku')->label()->requiredMapping()->guess([...])->rules([...])->example('X')->relationship('category','slug')->castStateUsing()->fillRecordUsing()->boolean()->numeric()->array()->ignoreBlankState()`.
- `Resource::importer(): ?class-string<Importer>` hook.
- `ImportAction::make(Importer::class)` — header action; validates upload + mapping, stores CSV, dispatches `PrepareImportJob`.
- `PrepareImportJob` → reads CSV, partitions into `ImportCsvChunkJob[]` via `Bus::batch()` → `CompleteImportJob`.
- `ImportCsvChunkJob`: map → cast → validate (`Validator::make` against column rules) → `saveRow()`; persists failures to `rocket_failed_import_rows`.
- `GET {panel}/imports/{id}` status page + `GET {panel}/imports/{id}/status` JSON poll endpoint + `GET {panel}/imports/{id}/failed-rows.csv` downloadable failure report.
- `GET {panel}/importers/{b64}/example.csv` sample generated from `example()` values.
- `POST {panel}/importers/{b64}/preview` returns auto-guessed column mapping + first 10 rows for the UI mapping modal.
- `ImportCompletedNotification` on completion.
- `rocket:make-importer {name} --model=`.

### Persistence
- Migrations (loadMigrationsFrom + `rocket-migrations` publish tag):
  - `rocket_exports` (id, exporter, file_name, file_disk, total_rows, successful_rows, batch_id, user_id, completed_at).
  - `rocket_imports` (adds processed_rows, failed_rows).
  - `rocket_failed_import_rows` (fk import_id, json data, validation_error).

### Config
- `rocket.exports.chunk_size` (default 100), `rocket.exports.disk` (default app disk).
- `rocket.imports.chunk_size` (default 100), `rocket.imports.disk`.

### Tests
- **20 new tests / 48 assertions** covering: column casting, CSV generation, end-to-end batched run (sync queue), chunk cleanup, empty-query handling, bulk + header action dispatch, download endpoint auth, mapping guess, validation failure persistence, example CSV, preview endpoint, failed-rows CSV, cross-user access denial.

### Not in scope (defer)
- XLSX (CSV only for now).
- Mapping persistence across uploads per user.
- "Reimport failed rows" shortcut button (data is there; UI only).

---

## Phase 7 — Panel Authentication

**Goal.** Each panel ships its own scoped auth pages (login, register, password reset, email verification) so the panel is fully self-contained — no dependency on the host app's auth routes. Fluent config mirrors Filament's API.

### Panel config API

```php
Panel::make()
    ->login()                          // enable panel-scoped login  (default: true)
    ->registration()                   // enable self-registration
    ->passwordReset()                  // enable forgot-password flow
    ->emailVerification()              // gate panel behind verified email
    ->profile()                        // enable /profile edit page inside the panel
    ->authGuard('admin')               // custom guard (default: 'web')
    ->authMiddleware(['auth:admin'])   // override the full middleware stack
    ->loginPage(MyLoginPage::class)    // swap in a custom login page class
    ->registrationPage(...)            // swap in a custom register page class
    ->passwordResetPage(...)
```

### Backend

- `PanelAuthController` — handles `showLogin`, `login`, `logout`, `showRegister`, `register`, `showForgot`, `sendResetLink`, `showReset`, `resetPassword`, `showVerify`, `resend`.
- Routes registered automatically under `{panel.path}/` only when the feature is enabled:
  - `GET  {panel}/login` · `POST {panel}/login` · `POST {panel}/logout`
  - `GET  {panel}/register` · `POST {panel}/register`
  - `GET  {panel}/forgot-password` · `POST {panel}/forgot-password`
  - `GET  {panel}/reset-password/{token}` · `POST {panel}/reset-password`
  - `GET  {panel}/verify-email` · `GET {panel}/verify-email/{id}/{hash}` · `POST {panel}/verify-email/resend`
  - `GET  {panel}/profile` · `PUT {panel}/profile`
- `Panel::authMiddleware()` replaces the default `['auth']` guard on protected routes with the panel-scoped equivalent.
- `Panel::login(false)` disables the panel login page and falls back to the host app's `/login`.
- Rate limiting: login throttled at 5 attempts / minute per IP + email (matches Fortify default).
- `CreatePanelUser` / `UpdatePanelProfile` action hooks — override to customize user creation or profile update logic per panel.
- `Panel::profile()` renders a profile edit page inside the panel chrome (name, email, password change).

### Frontend

- `login.tsx`, `register.tsx`, `forgot-password.tsx`, `reset-password.tsx`, `verify-email.tsx`, `profile.tsx` — standalone Inertia pages rendered inside a minimal auth layout (no sidebar), branded with `panel.brand` and `panel.theme`.
- Auth layout: centered card, panel logo/brand name, Tailwind v4, dark mode aware.
- Validation errors displayed inline per field (reuses existing Inertia error bag pattern).

### Tests

- Login: correct credentials redirect to panel, wrong credentials return validation error, throttle kicks in after 5 attempts.
- Register: creates user + redirects; disabled registration returns 404.
- Password reset: sends reset link email, valid token resets password, expired token returns error.
- Email verification: unverified user redirected to verify page, verified link marks email as verified.
- Profile: name/email update persists, password change requires current password.
- `Panel::login(false)` disables the route entirely.

### Done when

- Demo panel has login/register/forgot-password working end-to-end at `/rocket/login`.
- All auth pages respect the panel theme (primary color, radius, font).
- Switching `authGuard` to a custom guard routes auth through that guard.

---

## Phase 8 — Audit Log

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

## Phase 9 — Multi-tenancy (integration surface)

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
- [ ] Frontend bundle size audit — re-measure `rocket.js` post shadcn sidebar migration; watch for regressions. Recharts (Phase 4) is already lazy-loaded.

---

## Recommended order

Phases 1–6 shipped. Remaining:

7. **Panel Authentication (Phase 7)** — self-contained login/register/password-reset per panel; highest user-visible value.
8. **Audit log (Phase 8)** and **multi-tenancy (Phase 9)** — post-1.0 integration surfaces; drive by real consumer demand rather than speculation.

---

## Open design questions

- **Global search.** One shared endpoint that fans out across resources vs. per-resource endpoints behind a palette aggregator. Shared is simpler to consume; per-resource gives finer-grained caching and authorization. **Current lean:** shared endpoint, per-resource `take(5)` cap.
- **Theming surface.** Expose CSS variables (consumer controls via stylesheet), tokens serialized from PHP config, or both? **Current lean:** both — PHP config for the common 80%, CSS vars as the escape hatch.
- **Notifications storage.** Reuse Laravel's native `notifications` table, or introduce `rocket_notifications`? **Current lean:** reuse Laravel's — less surface area, plays nice with existing `Notifiable`.
- **Chart library.** Recharts (React-idiomatic, heavier) vs. Chart.js (lighter, less idiomatic) vs. ECharts (powerful, heaviest). **Current lean:** Recharts, lazy-loaded.
- **Form Tabs vs. Sections.** Keep Tabs as a wrapper over Section children, or add a `TabGroup` primitive supporting mixed tab / accordion per breakpoint? **Defer** until a real use case.
- **Color tokens vs. raw hex.** Keep both paths indefinitely, or deprecate raw hex? **Current lean:** keep both; raw hex is occasionally useful for per-record integrations (tag colors from a DB row).
- **Icon source.** Stay pinned to lucide names, or abstract? **Current lean:** pinned to lucide. Revisit only if a consumer ships their own icon library.
