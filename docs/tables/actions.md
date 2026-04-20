# Actions

Actions are row-level or bulk-level operations rendered on the table.
Built-in actions handle the CRUD basics; custom actions extend `Action`
or `BulkAction`.

## Row actions

```php
use MaherElGamil\Rocket\Tables\Actions\DeleteAction;
use MaherElGamil\Rocket\Tables\Actions\EditAction;
use MaherElGamil\Rocket\Tables\Actions\ViewAction;

$table->actions([
    ViewAction::make(),
    EditAction::make(),
    DeleteAction::make(),
]);
```

| Class | Policy | Behavior |
| --- | --- | --- |
| `ViewAction` | `view` | Link to the view page. |
| `EditAction` | `update` | Link to the edit page. |
| `DeleteAction` | `delete` | Delete with confirmation dialog. |

Row actions only render when the corresponding policy allows — denied
actions are not emitted to the schema.

## Bulk actions

Shown when the user selects rows via the row checkbox.

```php
use MaherElGamil\Rocket\Tables\Actions\BulkDeleteAction;

$table->bulkActions([
    BulkDeleteAction::make(),
]);
```

`BulkDeleteAction` confirms before deleting all selected records and
respects the `delete` policy per record.

## Custom actions

Extend `Action` (or `BulkAction`) and implement a handler. The class is
serialized into the table schema; a POST back to the action URL invokes
the handler server-side.

```php
use App\Mail\Welcome;
use Illuminate\Support\Facades\Mail;
use MaherElGamil\Rocket\Tables\Actions\Action;

final class ResendWelcomeEmail extends Action
{
    protected static ?string $label = 'Resend welcome email';
    protected static ?string $icon = 'mail';

    public function handle($record): void
    {
        Mail::to($record->email)->queue(new Welcome($record));
    }
}
```

Register it like the built-in actions:

```php
$table->actions([
    EditAction::make(),
    ResendWelcomeEmail::make(),
]);
```
