# Notifications

Rocket renders a notifications bell in the panel header that reads from
Laravel's standard `notifications` table. There's no custom channel — you
push notifications with the built-in `Notification::send()` facade and
Rocket displays them.

## Enable

On the panel:

```php
$panel->notificationsEnabled(true);
```

Make sure you've run Laravel's notifications migration:

```bash
php artisan notifications:table
php artisan migrate
```

## Send a notification

Use a standard Laravel notification and route it through the `database`
channel.

```php
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

final class PostPublished extends Notification
{
    use Queueable;

    public function __construct(public readonly int $postId, public readonly string $title) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Post published',
            'body' => $this->title,
            'icon' => 'check-circle',
            'url' => "/admin/posts/{$this->postId}",
        ]);
    }
}
```

Send it:

```php
$user->notify(new PostPublished($post->id, $post->title));
```

## Payload shape

Rocket reads the following keys from `data` on each notification. All are
optional — any missing field gets a sensible default.

| Key | Type | Default | Purpose |
| --- | --- | --- | --- |
| `title` | string | `data.message` · `'Notification'` | Headline shown in the bell and list. |
| `body` | string | `data.description` · `null` | Secondary text. |
| `icon` | string | `'bell'` | Icon name. |
| `url` | string | `data.action_url` · `null` | Click target. |

## Endpoints

The panel registers four notification routes under its path:

| Route | Method | Purpose |
| --- | --- | --- |
| `{panel}/notifications` | GET | Full paginated list (Inertia page). |
| `{panel}/notifications/recent` | GET | JSON — 10 most recent unread for the bell dropdown. |
| `{panel}/notifications/{id}/read` | POST | Mark a single notification read. |
| `{panel}/notifications/read-all` | POST | Mark all as read. |

Notifications are scoped to the authenticated user — each request reads
and updates only rows where `notifiable_type` and `notifiable_id` match
the current user.
