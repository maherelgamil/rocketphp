# Authorization

RocketPHP is policy-gated. Register a standard Laravel policy for each model
and Rocket enforces it on every resource operation.

## Policy methods checked

| Operation | Policy method |
| --- | --- |
| Show in sidebar / list page | `viewAny` |
| View record page | `view` |
| Create page + **New** button | `create` |
| Edit page + row action | `update` |
| Delete action (row + bulk) | `delete` |

If the current user fails `viewAny`, the resource is hidden from navigation
entirely — no stray 403 pages.

## Example policy

```php
namespace App\Policies;

use App\Models\Post;
use App\Models\User;

final class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('posts.read');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->can('posts.read');
    }

    public function create(User $user): bool
    {
        return $user->can('posts.write');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->can('posts.write');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->can('posts.delete');
    }
}
```

Register it with Laravel's auto-discovery (same `App\Models\X` →
`App\Policies\XPolicy` convention) or explicitly in `AuthServiceProvider`.

## Row-level checks

Policies receive the model instance on `view`, `update`, and `delete`, so
ownership checks work as in any Laravel app:

```php
public function update(User $user, Post $post): bool
{
    return $user->id === $post->author_id;
}
```

Rocket short-circuits row actions and page access based on these checks.
