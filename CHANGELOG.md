# Changelog

All notable changes to `maherelgamil/rocketphp` are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0);
the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Laravel **authorization**: resource routes and Inertia pages call `Gate` for
  `viewAny`, `create`, `update`, and `delete` on the resource model; sidebar
  hides resources the user cannot `viewAny`; `can` props for UI (create, update,
  delete).
- **Row and bulk actions**: `DeleteAction`, `BulkDeleteAction` (and extensible
  `Action` base), POST routes for row and bulk actions, React confirmations via
  AlertDialog, selection column when bulk actions exist.
- **Table filters**: `SelectFilter`, `TernaryFilter`, `DateRangeFilter`,
  `TrashedFilter` with `Table::filters()` and `applyFilters()`; filter bar in
  `DataTable` driven by query string.
- **Panel dashboard**: `GET {panel}/dashboard` with widget grid; `StatWidget`
  and `TableWidget` bases; React `Dashboard` page.
- **UX**: `TextColumn::copyable()` wired in the table; sidebar **navigation
  icons** (lucide name allowlist); **per-page** selector clamped to config.

### Changed
- README and PLAN.txt updated to reflect CRUD, policies, filters, actions, and
  dashboard.

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
