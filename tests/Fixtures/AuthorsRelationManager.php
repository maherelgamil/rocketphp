<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use MaherElGamil\Rocket\Resources\RelationManagers\RelationManager;
use MaherElGamil\Rocket\Tables\Columns\TextColumn;
use MaherElGamil\Rocket\Tables\Table;

final class AuthorsRelationManager extends RelationManager
{
    public static function getRelationship(): string
    {
        return 'authors';
    }

    public static function getRelatedModel(): string
    {
        return Author::class;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->sortable(),
            ])
            ->defaultSort('name', 'asc');
    }
}
