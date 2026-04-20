# Extending Rocket

Every primitive in Rocket — columns, fields, filters, widgets, actions,
blocks — is a PHP class that serializes to a schema plus a React
component that renders it. Adding a new one is always the same three
moves:

1. **PHP class** — subclass the base, implement `toArray()`.
2. **React renderer** — map the schema type to a component.
3. **Test** — assert the serialization.

If any of the three is missing, the primitive doesn't work end-to-end.

## Adding a table column

**1. PHP.** Subclass `Column` in `src/Tables/Columns/`:

```php
namespace MaherElGamil\Rocket\Tables\Columns;

use Illuminate\Database\Eloquent\Model;

final class ProgressColumn extends Column
{
    public function getValue(Model $record): mixed
    {
        return (int) ($record->{$this->name} ?? 0);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => 'progress',
        ]);
    }
}
```

**2. React.** In `resources/js/components/data-table.tsx`, extend the
switch that picks a cell renderer:

```tsx
case 'progress':
    return <ProgressCell value={row[col.name]} />;
```

And create `progress-cell.tsx` with the actual DOM.

**3. Test.** Add a feature test that asserts the JSON shape:

```php
test('ProgressColumn serializes type=progress', function () {
    expect(ProgressColumn::make('score')->toArray())
        ->toMatchArray(['type' => 'progress', 'name' => 'score']);
});
```

## Adding a form field

Same pattern. Subclass `Field` in `src/Forms/Components/`, implement
`toArray()` (include validation rules), add a React renderer, test the
schema. Laravel validation rules travel as part of the schema — the
client doesn't re-validate.

## Adding a filter

Subclass `Filter` in `src/Tables/Filters/`. Filters implement:

- `toArray()` — the schema
- `apply(Builder $query, mixed $value)` — how the filter mutates the
  query when the user picks a value

## Adding a widget

Subclass the appropriate base in `src/Dashboard/` (or extend `Widget`
directly for a new shape). Widgets return data + rendering hints; the
React widget renderer dispatches on `type`.

## Adding a page block

Subclass a base in `src/Pages/Blocks/`, serialize via `toArray()`, map
to a component in `block-renderer.tsx`.

## Beyond primitives

- **Custom Artisan commands** — register in a service provider in the
  host app.
- **Custom panel behavior** — extend `PanelProvider`; override
  `boot()` or `register()` for app-specific wiring.
- **Replacing the React frontend** — publish `rocket-assets` and point
  `assets.js_entry` at your copy. You own it from there.

See [Contributing](../contributing.md) for the expected PR shape when
you're upstreaming a primitive.
