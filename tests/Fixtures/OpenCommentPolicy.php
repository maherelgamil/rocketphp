<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use Illuminate\Auth\Access\HandlesAuthorization;

final class OpenCommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(?object $user): bool
    {
        return true;
    }
}
