<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use MaherElGamil\Rocket\Tables\Columns\TextColumn;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;

it('formats state as currency via ->money()', function () {
    $column = TextColumn::make('price')->money('USD', 'en_US');
    $record = new Widget(['price' => 1999.5]);

    expect($column->render($record))->toBe('$1,999.50');
});

it('formats state as number with fixed decimals via ->number()', function () {
    $column = TextColumn::make('count')->number(2, 'en_US');
    $record = new Widget(['count' => 1234.5]);

    expect($column->render($record))->toBe('1,234.50');
});

it('formats date-ish state via ->dateTime() and ->date()', function () {
    $record = new Widget(['at' => '2026-04-19 09:30:00']);

    expect(TextColumn::make('at')->dateTime('Y-m-d H:i')->render($record))
        ->toBe('2026-04-19 09:30');

    expect(TextColumn::make('at')->date('d/m/Y')->render($record))
        ->toBe('19/04/2026');
});

it('renders relative time via ->since()', function () {
    Carbon::setTestNow('2026-04-19 12:00:00');

    $column = TextColumn::make('at')->since();
    $record = new Widget(['at' => '2026-04-19 11:00:00']);

    expect($column->render($record))->toBe('1 hour ago');

    Carbon::setTestNow();
});

it('applies prefix, suffix, and limit in a predictable order', function () {
    $column = TextColumn::make('title')->limit(5, '…')->prefix('> ')->suffix('!');
    $record = new Widget(['title' => 'Hello world']);

    expect($column->render($record))->toBe('> Hello…!');
});

it('leaves null and empty states untouched', function () {
    $column = TextColumn::make('title')->money()->prefix('$');

    expect($column->render(new Widget(['title' => null])))->toBeNull();
    expect($column->render(new Widget(['title' => ''])))->toBe('');
});
