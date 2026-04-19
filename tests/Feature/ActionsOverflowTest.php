<?php

declare(strict_types=1);

use MaherElGamil\Rocket\Tables\Actions\DeleteAction;
use MaherElGamil\Rocket\Tables\Actions\EditAction;
use MaherElGamil\Rocket\Tables\Actions\ViewAction;
use MaherElGamil\Rocket\Tables\Table;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

it('defaults the overflow threshold to 3', function () {
    $table = Table::make(WidgetResource::class)
        ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);

    expect($table->getActionsOverflowAfter())->toBe(3);
});

it('honours a custom overflow threshold', function () {
    $table = Table::make(WidgetResource::class)
        ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
        ->actionsOverflowAfter(1);

    expect($table->getActionsOverflowAfter())->toBe(1);
});

it('clamps negative thresholds to zero', function () {
    $table = Table::make(WidgetResource::class)->actionsOverflowAfter(-5);

    expect($table->getActionsOverflowAfter())->toBe(0);
});
