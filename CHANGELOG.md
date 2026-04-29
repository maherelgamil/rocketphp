# Changelog

All notable changes to `maherelgamil/rocketphp` are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0);
the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

Targeting **1.0.0**. Consolidates all shipped work across Phases 1–7.

### Panels & routing
- `Panel` + `PanelManager` fluent config; auto-registered routes on register.
- `PanelProvider` abstract base for host-app panel definitions.
- `RenderRocketErrorPages` middleware converts 403 / 404 / 419 / 500 GET responses into Inertia error pages.
- Custom **Pages** discoverable via `Panel::discoverPages()`.

### Resources & CRUD
- `Resource` abstract: list, create, edit, view + store / update / destroy.
- Laravel **authorization** — `viewAny`, `view`, `create`, `update`, `delete` policy gates; sidebar auto-filters hidden resources.
- Row actions: `ViewAction`, `EditAction`, `DeleteAction`; bulk actions: `BulkDeleteAction`; overflow dropdown via `Table::actionsOverflowAfter()`.
- Panel-scoped AJAX lookup route for searchable relation fields.

### Tables
- `TextColumn` (money / number / date / dateTime / since / limit / prefix / suffix / markdown / copyable), `BadgeColumn`, `BooleanColumn`, `ImageColumn`, `IconColumn`.
- Filters: `SelectFilter`, `TernaryFilter`, `TrashedFilter`, `DateRangeFilter` (shadcn Popover + Calendar with preset sidebar).
- Search, sort, pagination, per-page selector clamped to config.
- `HasLabel` / `HasColor` / `HasIcon` enum contracts.

### Forms
- Fields: `TextInput`, `Textarea`, `Select`, `Checkbox`, `Radio`, `MultiSelect`, `Toggle`, `DatePicker`, `FileUpload`, `BelongsTo`, `BelongsToMany`, `KeyValue`.
- Layout: `Section`, `Tabs` (URL-hash persistence + per-tab validation-error dots).
- Enum-driven options on `Select` / `Radio` / `MultiSelect` via `EnumSupport`.
- Searchable `BelongsTo` with debounced shadcn combobox + auto-resolved selected label.
- Validation rules serialized to Inertia; inline error display.
- `Form::applyAfterSave()` + `Field::afterSave()` hooks.

### Relation managers
- `RelationManager` abstract + `RelationManagerRenderer` (policy-gated per `viewAny`).
- Namespaced search / sort / filters / pagination per manager (`rm_{name}_*`).
- `Resource::relationManagers()` declaration; `relationManagersLayout()` toggles tabs (default, with record-count badges, URL-hash persistence) vs. stacked.
- `rocket.pagination.relation_manager.per_page` (default `5`).

### Dashboard & widgets
- `StatWidget`, `ChartWidget`, `TableWidget`, `RecentRecordsWidget`, `ActivityFeedWidget`.
- `Resource::widgets()` + `CanRenderOnPages` trait with `->only(['list', 'create', 'edit', 'view'])`.
- Lazy-loaded Recharts; column span, loading skeleton, empty state.

### Global search
- `Cmd+K` palette with cross-resource fan-out; per-resource `take(5)`, overall cap 50; `viewAny`-gated.
- `Resource::globalSearchColumns()`, `globalSearchResult()`, `getGlobalSearchEloquentQuery()` hooks.
- `Panel::globalSearchEnabled()` + `globalSearchPlaceholder()`.

### Theming
- `Panel::setPrimaryColor()`, `setAccentColor()`, `setFont()`, `setRadius()`, `setDensity()`.
- Tokens serialized into shared props; CSS variables injected via `PanelShell`.
- Density scales `--rocket-gap`, `--rocket-input-height`, `--rocket-font-size`.

### Notifications center
- `Panel::notificationsEnabled()` + shared prop `notifications = { enabled, urls }`.
- `NotificationController` with `index`, `recent`, `markRead`, `markAllRead`.
- Topbar bell icon with count badge + popover.

### i18n & RTL
- `Panel::locale(string | Closure)` independent of app default.
- `rocket-lang` publish tag with `en.json` + `ar.json`.
- RTL-aware `PanelShell` shipped alongside shadcn sidebar migration.

### Import / export (CSV)
- `Exporter` abstract + `ExportColumn` DSL; `Resource::exporter()` hook.
- `ExportAction` (full filtered query) + `ExportBulkAction` (selection); `Table::headerActions()`.
- `Bus::batch()` orchestration: `PrepareExportJob` → `ExportCsvChunkJob[]` → `CompleteExportJob`; `ExportReadyNotification`.
- `Importer` abstract + `ImportColumn` DSL with `requiredMapping`, `guess`, `rules`, `example`, `relationship`, `castStateUsing`, `fillRecordUsing`, etc.
- `ImportAction` with mapping modal; status page + JSON poll endpoint; failed-rows CSV download; `ImportCompletedNotification`.
- Migrations: `rocket_exports`, `rocket_imports`, `rocket_failed_import_rows` (loadMigrationsFrom + `rocket-migrations` publish tag).
- `rocket:make-exporter` / `rocket:make-importer` generators.
- Config: `rocket.exports.chunk_size`, `rocket.exports.disk`, `rocket.imports.chunk_size`, `rocket.imports.disk`.

### Panel authentication
- `Panel::login()`, `registration()`, `passwordReset()`, `emailVerification()`, `profile()`, `authGuard()`, `authMiddleware()`, custom `*Page()` overrides.
- `PanelAuthController` + per-IP+email login throttle (5/min); panel-scoped `Authenticate` middleware.
- `CreatePanelUser` / `UpdatePanelProfile` action hooks.
- Standalone Inertia auth pages branded by `panel.brand` and `panel.theme`; sidebar footer user menu.

### Frontend
- Self-contained React entrypoint (`rocket.tsx`) + pages: list, create, edit, view, dashboard, error, auth.
- shadcn components: badge, button, card, input, label, select, skeleton, switch, table, textarea, popover, calendar, sonner, dropdown-menu, sheet, sidebar.
- Responsive `PanelShell`: collapsible desktop sidebar + mobile slide-in drawer.
- Flash-to-toast wiring via `useFlashToast`; semantic palette mapping with `dark:` variants; lucide icons via `lucide-react/dynamic`.

### Tooling
- Artisan generators: `rocket:make-panel`, `rocket:make-resource`, `rocket:make-page`, `rocket:make-exporter`, `rocket:make-importer`.
- Publish tags: `rocket-config`, `rocket-stubs`, `rocket-lang`, `rocket-migrations`.

### Tests
- **278 tests / 630 assertions** across resources, forms, auth, actions, filters, dashboard, enum support, color tokens, relation managers, import/export.

### CI
- GitHub Actions matrix: PHP 8.2 / 8.3 / 8.4 × Laravel 11 / 12 / 13 (PHP 8.2 × Laravel 13 excluded); separate Pint code-style job.

### Added (historical)
- Initial package scaffold: `Panel`, `PanelManager`, `PanelProvider`
- `Resource` abstract + `ListRecords` page
- `Table` with `TextColumn` and `BadgeColumn`
- `ResourceController` dispatches panel + slug to Inertia pages
- `HandleRocketRequests` Inertia middleware (root view, shared props, asset version)
- Artisan: `rocket:make-panel`, `rocket:make-resource`
- Self-contained React layer built on shadcn/ui + Tailwind v4
- Filament-style `Panel::discoverResources(in:, for:)` convention scanner
- Laravel-style `config/rocket.php` with env-driven defaults
- Orchestra Testbench suite with Pest
- MIT license, README, CI via GitHub Actions
