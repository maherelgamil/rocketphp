<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use MaherElGamil\Rocket\Forms\Components\KeyValue;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;

it('serializes key_value type with labels', function () {
    $field = KeyValue::make('meta')
        ->keyLabel('Name')
        ->valueLabel('Setting')
        ->addButtonLabel('+ Add');

    $schema = $field->toArray();

    expect($schema['type'])->toBe('key_value');
    expect($schema['extra'])->toBe([
        'key_label' => 'Name',
        'value_label' => 'Setting',
        'add_button_label' => '+ Add',
    ]);
    expect($field->getValidationRules())->toContain('array');
});

it('converts an assoc array state to pairs for the frontend', function () {
    $field = KeyValue::make('meta');
    $record = new Widget(['meta' => ['theme' => 'dark', 'size' => 'lg']]);

    expect($field->getState($record))->toBe([
        ['key' => 'theme', 'value' => 'dark'],
        ['key' => 'size', 'value' => 'lg'],
    ]);
});

it('decodes a JSON string state into pairs', function () {
    $field = KeyValue::make('meta');
    $record = new Widget(['meta' => '{"a":"1","b":"2"}']);

    expect($field->getState($record))->toBe([
        ['key' => 'a', 'value' => '1'],
        ['key' => 'b', 'value' => '2'],
    ]);
});

it('accepts a pairs payload and produces an assoc array on submission', function () {
    $field = KeyValue::make('meta');

    $submitted = [
        ['key' => 'theme', 'value' => 'dark'],
        ['key' => '',      'value' => 'ignored'],
        ['key' => 'size',  'value' => 'lg'],
    ];

    $result = $field->processSubmission(Request::create('/', 'POST'), $submitted, null);

    expect($result)->toBe(['theme' => 'dark', 'size' => 'lg']);
});

it('returns an empty array when the submission is not an array', function () {
    $field = KeyValue::make('meta');

    expect($field->processSubmission(Request::create('/', 'POST'), null, null))->toBe([]);
});
