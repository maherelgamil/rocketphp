<?php

declare(strict_types=1);

use MaherElGamil\Rocket\Tables\Columns\TextColumn;

it('defaults toggleable to false in schema', function () {
    $schema = TextColumn::make('email')->toArray();

    expect($schema)
        ->toHaveKey('toggleable', false)
        ->toHaveKey('toggled_hidden_by_default', false);
});

it('marks a column as toggleable via ->toggleable()', function () {
    $column = TextColumn::make('email')->toggleable();

    expect($column->isToggleable())->toBeTrue();
    expect($column->isToggledHiddenByDefault())->toBeFalse();
    expect($column->toArray())->toMatchArray([
        'toggleable' => true,
        'toggled_hidden_by_default' => false,
    ]);
});

it('toggles hidden-by-default via ->toggleable(true, true)', function () {
    $column = TextColumn::make('email')->toggleable(true, true);

    expect($column->isToggleable())->toBeTrue();
    expect($column->isToggledHiddenByDefault())->toBeTrue();
    expect($column->toArray())->toMatchArray([
        'toggleable' => true,
        'toggled_hidden_by_default' => true,
    ]);
});

it('->toggledHiddenByDefault() implies toggleable', function () {
    $column = TextColumn::make('email')->toggledHiddenByDefault();

    expect($column->isToggleable())->toBeTrue();
    expect($column->isToggledHiddenByDefault())->toBeTrue();
});

it('->toggleable(false) disables toggleability', function () {
    $column = TextColumn::make('email')->toggleable()->toggleable(false);

    expect($column->isToggleable())->toBeFalse();
});
