# Page Actions

Pages expose a toolbar of **actions** — the buttons rendered in the
page header. Use them for primary tasks (publish, export, re-run) and
secondary links that don't belong in the page body.

```php
use MaherElGamil\Rocket\Pages\Actions\Action;
use MaherElGamil\Rocket\Panel\Panel;

final class PublishQueuePage extends ResourcePage
{
    public function actions(): array
    {
        return [
            Action::make('publish_all')
                ->label('Publish all')
                ->icon('rocket')
                ->color('primary')
                ->requiresConfirmation()
                ->confirmationMessage('This will publish every queued post.')
                ->action(fn () => Post::queued()->publish()),

            Action::make('open_docs')
                ->label('Docs')
                ->url('https://example.com/docs'),
        ];
    }
}
```

## The Action API

| Method | Purpose |
| --- | --- |
| `Action::make(string $name)` | Unique name used as the form field when the action is posted. |
| `->label(string)` | Visible button text. |
| `->icon(?string)` | Lucide icon name. |
| `->color(string)` | Token name — `primary`, `danger`, `success`, etc. See [panel colors](../panels/configuration.md). |
| `->url(string\|Closure)` | Render as a link instead of a form button. |
| `->action(Closure)` | Run server-side PHP when the button is pressed. |
| `->requiresConfirmation(bool = true)` | Prompt before invoking. |
| `->confirmationMessage(string)` | Customise the prompt body. |
| `->visible(bool\|Closure)` | Hide conditionally — closures are evaluated at serialization time. |

Only one of `->url()` or `->action()` will apply per button. URLs
render as anchors; actions render as form buttons that POST back to
the page handler.

## Authorization

Actions serialize through `isVisible()`, so wrap policy checks in the
closure:

```php
Action::make('delete_all')
    ->label('Delete all')
    ->color('danger')
    ->visible(fn () => Gate::allows('delete-any', Post::class))
    ->action(fn () => Post::query()->delete());
```

The client never sees hidden actions — they're stripped from the
schema before Inertia renders.

## Related

- [Pages overview](overview.md) — where actions fit in the page
  lifecycle.
- [Table Actions](../tables/actions.md) — per-row and bulk actions on
  data tables (different API, same idea).
