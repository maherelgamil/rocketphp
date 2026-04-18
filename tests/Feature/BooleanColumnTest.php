<?php

declare(strict_types=1);

use MaherElGamil\Rocket\Tables\Columns\BooleanColumn;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;

it('serializes icon and color tokens with sensible defaults', function () {
    $schema = BooleanColumn::make('is_featured')->toArray();

    expect($schema['type'])->toBe('boolean');
    expect($schema['extra'])->toBe([
        'true_icon' => 'check',
        'false_icon' => 'x',
        'true_color' => 'green',
        'false_color' => 'slate',
    ]);
});

it('allows customising icons and palette tokens', function () {
    $schema = BooleanColumn::make('is_featured')
        ->trueIcon('star')
        ->falseIcon('circle')
        ->trueColor('blue')
        ->falseColor('gray')
        ->toArray();

    expect($schema['extra'])->toBe([
        'true_icon' => 'star',
        'false_icon' => 'circle',
        'true_color' => 'blue',
        'false_color' => 'gray',
    ]);
});

it('reads the raw boolean state from the record', function () {
    $column = BooleanColumn::make('is_featured');

    expect($column->getState(new Widget(['is_featured' => true])))->toBeTrue();
    expect($column->getState(new Widget(['is_featured' => false])))->toBeFalse();
});
