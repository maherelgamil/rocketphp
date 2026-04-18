<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use MaherElGamil\Rocket\Forms\Components\Select;
use MaherElGamil\Rocket\Support\EnumSupport;
use MaherElGamil\Rocket\Tables\Columns\BadgeColumn;
use MaherElGamil\Rocket\Tables\Filters\SelectFilter;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetStatus;

it('extracts options from a backed enum using HasLabel', function () {
    expect(EnumSupport::toOptions(WidgetStatus::class))->toBe([
        'active' => 'Active Widget',
        'draft' => 'Draft Widget',
        'archived' => 'Archived Widget',
    ]);
});

it('extracts colors from a backed enum using HasColor and skips null colors', function () {
    expect(EnumSupport::toColors(WidgetStatus::class))->toBe([
        'active' => '#16a34a',
        'draft' => '#64748b',
    ]);
});

it('hydrates Select options from an enum and validates against enum values', function () {
    $select = Select::make('status')->enum(WidgetStatus::class)->required();

    expect($select->getValidationRules())->toContain('in:active,draft,archived');
});

it('hydrates SelectFilter options from an enum', function () {
    $filter = (new SelectFilter('status', 'status', 'Status'))->enum(WidgetStatus::class);

    $schema = $filter->toSchema(new Request);

    expect($schema['options'])->toBe([
        'active' => 'Active Widget',
        'draft' => 'Draft Widget',
        'archived' => 'Archived Widget',
    ]);
});

it('hydrates BadgeColumn colors from an enum and renders enum labels', function () {
    $column = BadgeColumn::make('status')->enum(WidgetStatus::class);

    expect($column->toArray()['extra']['colors'])->toBe([
        'active' => '#16a34a',
        'draft' => '#64748b',
    ]);

    $record = new Widget(['status' => 'active']);

    expect($column->getState($record))->toBe('active');
    expect($column->render($record))->toBe('Active Widget');
});

it('preserves enum value when BadgeColumn state is a UnitEnum instance', function () {
    $column = BadgeColumn::make('status')->enum(WidgetStatus::class);

    $record = new Widget;
    $record->setRawAttributes(['status' => WidgetStatus::Draft]);

    expect($column->getState($record))->toBe('draft');
    expect($column->render($record))->toBe('Draft Widget');
});

it('throws when a non-enum class is passed to enum helpers', function () {
    EnumSupport::toOptions(stdClass::class);
})->throws(InvalidArgumentException::class);
