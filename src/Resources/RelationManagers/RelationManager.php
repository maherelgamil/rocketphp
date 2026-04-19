<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Resources\RelationManagers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use MaherElGamil\Rocket\Tables\Table;

abstract class RelationManager
{
    abstract public static function getRelationship(): string;

    abstract public static function table(Table $table): Table;

    /**
     * @return class-string<Model>
     */
    abstract public static function getRelatedModel(): string;

    public static function getName(): string
    {
        return (string) Str::of(static::getRelationship())->snake();
    }

    public static function getTitle(): string
    {
        return (string) Str::of(static::getRelationship())->headline();
    }

    public static function query(Model $ownerRecord): Builder
    {
        /** @var \Illuminate\Database\Eloquent\Relations\Relation $relation */
        $relation = $ownerRecord->{static::getRelationship()}();

        return $relation->getQuery();
    }
}
