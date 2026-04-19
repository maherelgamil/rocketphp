<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use MaherElGamil\Rocket\Resources\RelationManagers\RelationManager;
use MaherElGamil\Rocket\Tables\Columns\TextColumn;
use MaherElGamil\Rocket\Tables\Filters\SelectFilter;
use MaherElGamil\Rocket\Tables\Table;

final class CommentsRelationManager extends RelationManager
{
    public static function getRelationship(): string
    {
        return 'comments';
    }

    public static function getRelatedModel(): string
    {
        return Comment::class;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('body')->sortable(),
                TextColumn::make('status'),
            ])
            ->searchable(['body'])
            ->filters([
                new SelectFilter('status', 'status', 'Status', [
                    'approved' => 'Approved',
                    'pending' => 'Pending',
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
