# Server-Driven UI

RocketPHP is built on **Server-Driven UI** (SDUI): the server controls not
just the data, but *what components to render, how they're laid out, and
how they behave*. The client is a deterministic renderer — it never decides
what to show.

## The traditional split

A typical Inertia or SPA app divides responsibilities like this:

```
PHP       →  data (JSON)
React     →  presentation, layout, validation display, state, URL handling
```

Every new screen needs backend data plumbing **and** frontend components,
types, hooks, and forms. Validation rules are usually duplicated.

## The SDUI split

RocketPHP shifts the boundary:

```
PHP       →  UI schema (components + layout + behavior + data + validation)
React     →  deterministic renderer
```

The server sends something like:

```json
{
  "type": "table",
  "columns": [
    { "type": "text", "name": "id", "sortable": true },
    { "type": "badge", "name": "status", "colors": { "published": "green" } }
  ],
  "filters": [{ "type": "select", "name": "status", "options": [...] }],
  "rows": [...],
  "pagination": { "current": 1, "last": 8, "perPage": 25 }
}
```

The React renderer sees `type: "table"` and produces the UI. No resource-
specific frontend code exists.

## What the client does

- Renders schemas produced by the server
- Forwards user intent back as HTTP requests (Inertia visits / form POSTs)
- Handles *purely presentational* concerns: animations, focus, keyboard nav

## What the client never does

- Decides what fields a form has
- Decides what columns a table shows
- Decides what action buttons to render
- Validates input (it displays server-returned errors)
- Owns entity state

## Why this works

- **Single source of truth.** A column's label, sort behavior, formatting,
  and visibility live in one PHP file.
- **No API surface to design.** There is no `/api/users` contract to version.
  The schema *is* the contract, and it changes with the PHP definition.
- **Policies run where data lives.** Authorization happens on the server
  before the schema is emitted; unauthorized actions never appear.
- **Shipping speed.** Adding a column is a one-line change. Adding a
  resource is a single PHP class.

## Trade-offs

SDUI trades flexibility for consistency.

- Highly custom, interaction-heavy screens (drag-and-drop canvases,
  real-time collaborative editors) are usually better built as bespoke
  Inertia pages — Rocket can coexist with them.
- Every new primitive (column type, field type, widget) requires **both**
  a PHP serializer and a React renderer. Rocket ships with enough
  primitives to cover standard CRUD; custom ones are a small deliberate
  investment.

See [Resources](../resources/overview.md) to see SDUI applied end-to-end.
