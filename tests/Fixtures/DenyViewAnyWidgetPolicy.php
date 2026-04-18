<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use Illuminate\Auth\Access\HandlesAuthorization;

final class DenyViewAnyWidgetPolicy
{
    use HandlesAuthorization;

    public function viewAny(?object $user): bool
    {
        return false;
    }

    public function view(?object $user, Widget $widget): bool
    {
        return false;
    }

    public function create(?object $user): bool
    {
        return false;
    }

    public function update(?object $user, Widget $widget): bool
    {
        return false;
    }

    public function delete(?object $user, Widget $widget): bool
    {
        return false;
    }
}
