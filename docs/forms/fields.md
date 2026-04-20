# Forms

Forms are declared with the `Form` builder and rendered by React from a
serialized schema. Validation rules travel with the schema — errors are
displayed inline without a client round-trip.

`form()` is **optional** on a resource. If you don't define one, the
create/edit pages and the **New** button are hidden automatically — useful
for read-only resources backed by an external system.

```php
use MaherElGamil\Rocket\Forms\Components\Select;
use MaherElGamil\Rocket\Forms\Components\TextInput;
use MaherElGamil\Rocket\Forms\Components\Textarea;
use MaherElGamil\Rocket\Forms\Form;

public static function form(Form $form): Form
{
    return $form->schema([
        TextInput::make('title')->required()->maxLength(255),
        Textarea::make('body')->rows(6),
        Select::make('status')->options([
            'draft' => 'Draft',
            'published' => 'Published',
        ])->required(),
    ]);
}
```

## Field types

| Class | Purpose |
| --- | --- |
| `TextInput` | Single-line text (supports `email`, `password`, `number` variants). |
| `Textarea` | Multi-line text. |
| `Select` | Single-select dropdown. |
| `MultiSelect` | Multi-select dropdown. |
| `Checkbox` | Single boolean checkbox. |
| `Radio` | Radio group. |
| `Toggle` | Switch toggle for booleans. |
| `DatePicker` | Calendar picker. |
| `BelongsTo` | Relationship dropdown (loads options from a related model). |
| `BelongsToMany` | Multi-select relationship. |
| `FileUpload` | File input with preview. |
| `KeyValue` | Dynamic key/value pairs (arrays, JSON columns). |

## Common modifiers

- `->label(string)` — override the auto-generated label
- `->required()` / `->nullable()`
- `->disabled()` / `->readOnly()`
- `->placeholder(string)`
- `->helperText(string)`
- `->default(mixed)`
- `->columnSpan(int)` — width in the grid (1–12)
- `->hidden()` / `->visible(bool)`

Validation modifiers (serialized as Laravel rules):

- `->minLength(int)` / `->maxLength(int)`
- `->min(int)` / `->max(int)`
- `->email()` / `->url()` / `->regex(string)`
- `->unique()` / `->exists()`
- `->confirmed()` (for password confirmation)

## Layout

Group fields with `Section` and `Tabs`:

```php
use MaherElGamil\Rocket\Forms\Components\Section;
use MaherElGamil\Rocket\Forms\Components\Tabs;

Section::make('Details')
    ->description('Basic info')
    ->schema([
        TextInput::make('title'),
        TextInput::make('slug'),
    ]);

Tabs::make('Post')
    ->tabs([
        Tabs\Tab::make('Content')->schema([ /* ... */ ]),
        Tabs\Tab::make('SEO')->schema([ /* ... */ ]),
    ]);
```
