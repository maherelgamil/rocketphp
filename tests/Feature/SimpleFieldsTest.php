<?php

declare(strict_types=1);

use MaherElGamil\Rocket\Forms\Components\Checkbox;
use MaherElGamil\Rocket\Forms\Components\MultiSelect;
use MaherElGamil\Rocket\Forms\Components\Radio;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetStatus;

it('Checkbox field emits boolean validation and checkbox type', function () {
    $field = Checkbox::make('is_active')->required();

    expect($field->type())->toBe('checkbox');
    expect($field->getValidationRules())->toContain('boolean');
    expect($field->getValidationRules())->toContain('required');
});

it('Radio field exposes options and in: validation', function () {
    $field = Radio::make('status')
        ->options(['draft' => 'Draft', 'live' => 'Live'])
        ->inline()
        ->required();

    expect($field->type())->toBe('radio');
    expect($field->toArray()['extra'])->toBe([
        'options' => ['draft' => 'Draft', 'live' => 'Live'],
        'inline' => true,
    ]);
    expect($field->getValidationRules())->toContain('in:draft,live');
});

it('Radio field hydrates options from an enum', function () {
    $field = Radio::make('status')->enum(WidgetStatus::class);

    expect(array_keys($field->toArray()['extra']['options']))->toBe(['active', 'draft', 'archived']);
});

it('MultiSelect field emits array validation and exposes options', function () {
    $field = MultiSelect::make('tags')
        ->options(['a' => 'Alpha', 'b' => 'Beta']);

    expect($field->type())->toBe('multi_select');
    expect($field->getValidationRules())->toContain('array');
    expect($field->toArray()['extra']['options'])->toBe(['a' => 'Alpha', 'b' => 'Beta']);
});

it('MultiSelect field hydrates options from an enum', function () {
    $field = MultiSelect::make('statuses')->enum(WidgetStatus::class);

    expect(array_keys($field->toArray()['extra']['options']))->toBe(['active', 'draft', 'archived']);
});
