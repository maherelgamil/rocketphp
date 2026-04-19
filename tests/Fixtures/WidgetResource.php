<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use MaherElGamil\Rocket\Forms\Components\FileUpload;
use MaherElGamil\Rocket\Forms\Components\Select;
use MaherElGamil\Rocket\Forms\Components\Textarea;
use MaherElGamil\Rocket\Forms\Components\TextInput;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Resources\Resource;
use MaherElGamil\Rocket\Tables\Actions\BulkDeleteAction;
use MaherElGamil\Rocket\Tables\Actions\DeleteAction;
use MaherElGamil\Rocket\Tables\Columns\BadgeColumn;
use MaherElGamil\Rocket\Tables\Columns\TextColumn;
use MaherElGamil\Rocket\Tables\Filters\DateRangeFilter;
use MaherElGamil\Rocket\Tables\Filters\SelectFilter;
use MaherElGamil\Rocket\Tables\Filters\TernaryFilter;
use MaherElGamil\Rocket\Tables\Filters\TrashedFilter;
use MaherElGamil\Rocket\Tables\Table;

class WidgetResource extends Resource
{
    protected static string $model = Widget::class;

    protected static ?string $slug = 'widgets';

    protected static ?string $navigationIcon = 'package';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->sortable()->copyable(),
                BadgeColumn::make('status')->colors([
                    'active' => '#16a34a',
                    'draft' => '#64748b',
                ]),
            ])
            ->searchable(['name'])
            ->filters([
                new SelectFilter('status', 'status', 'Status', [
                    'active' => 'Active',
                    'draft' => 'Draft',
                ]),
                new TernaryFilter('featured', 'is_featured', 'Featured'),
                new DateRangeFilter('published', 'published_at', 'Published'),
                new TrashedFilter,
            ])
            ->actions([DeleteAction::make()])
            ->bulkActions([BulkDeleteAction::make()])
            ->defaultSort('id', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->max(255),
            Textarea::make('description')->nullable()->max(2000),
            Select::make('status')->options([
                'active' => 'Active',
                'draft' => 'Draft',
            ])->required(),
            FileUpload::make('avatar')
                ->image()
                ->directory('widgets')
                ->maxSize(1024)
                ->nullable(),
        ]);
    }
}
