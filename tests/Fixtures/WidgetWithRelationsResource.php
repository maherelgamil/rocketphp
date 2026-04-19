<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use MaherElGamil\Rocket\Resources\RelationManagers\RelationManager;

final class WidgetWithRelationsResource extends WidgetResource
{
    protected static ?string $slug = 'widgets-with-relations';

    /**
     * @return array<int, class-string<RelationManager>>
     */
    public static function relationManagers(): array
    {
        return [
            CommentsRelationManager::class,
            AuthorsRelationManager::class,
        ];
    }
}
