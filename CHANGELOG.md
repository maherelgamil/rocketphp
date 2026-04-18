# Changelog

All notable changes to `maherelgamil/rocketphp` are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Initial package scaffold: `Panel`, `PanelManager`, `PanelProvider`
- `Resource` abstract + `ListRecords` page
- `Table` with `TextColumn` and `BadgeColumn`
- `ResourceController` dispatches panel + slug to Inertia pages
- `HandleRocketRequests` Inertia middleware (root view, shared props, asset version)
- Artisan: `rocket:make-panel`, `rocket:make-resource`
- Self-contained React layer built on shadcn/ui + Tailwind v4
- Filament-style `Panel::discoverResources(in:, for:)` convention scanner
- Laravel-style `config/rocket.php` with env-driven defaults
- Orchestra Testbench suite with Pest (4 passing feature tests)
- MIT license, README, CI via GitHub Actions
