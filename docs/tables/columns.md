# Tables

Declare a resource's list page with the `Table` builder.

```php
use MaherElGamil\Rocket\Tables\Actions\DeleteAction;
use MaherElGamil\Rocket\Tables\Actions\EditAction;
use MaherElGamil\Rocket\Tables\Columns\BadgeColumn;
use MaherElGamil\Rocket\Tables\Columns\TextColumn;
use MaherElGamil\Rocket\Tables\Filters\SelectFilter;
use MaherElGamil\Rocket\Tables\Table;

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('name')->sortable()->searchable(),
            BadgeColumn::make('status'),
        ])
        ->filters([
            SelectFilter::make('status')->options([
                'draft' => 'Draft',
                'published' => 'Published',
            ]),
        ])
        ->actions([EditAction::make(), DeleteAction::make()])
        ->searchable(['name', 'email'])
        ->defaultSort('id', 'desc')
        ->perPageOptions([10, 25, 50, 100]);
}
```

## Columns

| Class | Renders |
| --- | --- |
| `TextColumn` | Plain text, with optional `copyable()`, `badge()`, formatting. |
| `BadgeColumn` | Coloured pill — pair with `HasColor` enums. |
| `BooleanColumn` | ✓ / ✗ indicator. |
| `IconColumn` | Single icon from the icon set. |
| `ImageColumn` | Square image thumbnail (avatar / logo). |

Common column modifiers:

- `->label(string)` — override the header label
- `->sortable()` — click-to-sort
- `->searchable()` — include in global search
- `->hidden()` / `->visible(bool)` — conditional visibility
- `->alignRight()` / `->alignCenter()`
- `->formatStateUsing(callable)` — transform the value before display

## Adding a custom column

Every column is a PHP class that serializes to a schema plus a React
component that renders it. Adding one is always three moves: PHP class,
React renderer, test.

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

Create `progress-cell.tsx` with the actual DOM.

**3. Test.** Add a feature test that asserts the schema:

```php
test('ProgressColumn serializes type=progress', function () {
    expect(ProgressColumn::make('score')->toArray())
        ->toMatchArray(['type' => 'progress', 'name' => 'score']);
});
```

See [Contributing](../contributing.md) for the expected PR shape when
upstreaming a primitive.

## Filters

| Class | Purpose |
| --- | --- |
| `SelectFilter` | Single-select dropdown. |
| `TernaryFilter` | Yes / No / All tri-state. |
| `DateRangeFilter` | Between two dates. |
| `TrashedFilter` | Active / trashed / all (for soft-deletes). |

## Actions

Row actions:

- `EditAction` — link to the edit page
- `ViewAction` — link to the view page
- `DeleteAction` — delete with confirmation dialog

Bulk actions (checkbox selection):

- `BulkDeleteAction` — delete selected with confirmation

Define custom row or bulk actions by extending `Action` or `BulkAction`.

## Search, sort, paginate

- `->searchable([...])` — global search across columns
- `->defaultSort($column, $direction)` — initial sort
- `->perPageOptions([...])` — user-selectable page sizes
