<?php

declare(strict_types=1);

use MaherElGamil\Rocket\Forms\Components\Section;
use MaherElGamil\Rocket\Forms\Components\TextInput;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

it('serializes a section node alongside flat fields', function () {
    $form = Form::make(WidgetResource::class)->schema([
        Section::make('Content')
            ->description('Basic info')
            ->columns(2)
            ->schema([
                TextInput::make('title')->required(),
                TextInput::make('slug'),
            ]),
        TextInput::make('notes'),
    ]);

    $schema = $form->toArray();

    expect($schema['fields'])->toHaveCount(2);
    expect($schema['fields'][0])->toMatchArray([
        'type' => 'section',
        'label' => 'Content',
        'description' => 'Basic info',
        'columns' => 2,
        'collapsible' => false,
        'collapsed' => false,
    ]);
    expect($schema['fields'][0]['fields'])->toHaveCount(2);
    expect($schema['fields'][0]['fields'][0]['name'])->toBe('title');
    expect($schema['fields'][1]['name'])->toBe('notes');
});

it('flattens section fields for validation and defaults', function () {
    $form = Form::make(WidgetResource::class)->schema([
        Section::make('Group A')->schema([
            TextInput::make('a')->required(),
            TextInput::make('b')->default('hello'),
        ]),
        TextInput::make('c')->required(),
    ]);

    expect(array_keys($form->getValidationRules()))->toBe(['a', 'b', 'c']);
    expect($form->getDefaults())->toBe(['a' => null, 'b' => 'hello', 'c' => null]);
});

it('marks sections as collapsible when ->collapsed() is used', function () {
    $section = Section::make('Advanced')->collapsed();

    expect($section->toArray())->toMatchArray([
        'collapsible' => true,
        'collapsed' => true,
    ]);
});
