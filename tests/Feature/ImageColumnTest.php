<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use MaherElGamil\Rocket\Tables\Columns\ImageColumn;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;

it('resolves a storage path to a public URL via the configured disk', function () {
    Storage::fake('public');
    Storage::disk('public')->put('widgets/a.png', 'stub');

    $column = ImageColumn::make('avatar')->disk('public')->size(64)->circular();
    $record = new Widget(['avatar' => 'widgets/a.png']);

    expect($column->render($record))->toBe(Storage::disk('public')->url('widgets/a.png'));

    $schema = $column->toArray();
    expect($schema['type'])->toBe('image');
    expect($schema['extra'])->toBe(['size' => 64, 'circular' => true]);
});

it('passes absolute URLs through unchanged', function () {
    $column = ImageColumn::make('avatar');
    $record = new Widget(['avatar' => 'https://example.com/a.png']);

    expect($column->render($record))->toBe('https://example.com/a.png');
});

it('returns the fallback when the state is empty', function () {
    $column = ImageColumn::make('avatar')->fallback('https://example.com/placeholder.png');
    $record = new Widget(['avatar' => null]);

    expect($column->render($record))->toBe('https://example.com/placeholder.png');
});
