<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Forms\Components\BelongsTo;
use MaherElGamil\Rocket\Tests\Fixtures\Author;

beforeEach(function () {
    Schema::dropIfExists('authors');
    Schema::create('authors', function ($table) {
        $table->id();
        $table->string('name');
        $table->boolean('is_active')->default(true);
    });

    Author::insert([
        ['name' => 'Beatrice'],
        ['name' => 'Zelda'],
        ['name' => 'Arthur'],
    ]);
});

it('loads options from the related model sorted by the title column', function () {
    $field = BelongsTo::make('author_id')->related(Author::class, 'name');

    $extra = $field->toArray()['extra'];

    expect(array_values($extra['options']))->toBe(['Arthur', 'Beatrice', 'Zelda']);
    expect($extra['searchable'])->toBeFalse();
});

it('adds an exists validation rule scoped to the related table', function () {
    $field = BelongsTo::make('author_id')->related(Author::class)->required();

    expect($field->getValidationRules())->toContain('exists:authors,id');
});

it('applies a modifyQuery closure before loading options', function () {
    Author::where('name', 'Zelda')->update(['is_active' => false]);

    $field = BelongsTo::make('author_id')
        ->related(Author::class, 'name')
        ->modifyQuery(fn ($query) => $query->where('is_active', true));

    $options = $field->toArray()['extra']['options'];

    expect(array_values($options))->toBe(['Arthur', 'Beatrice']);
});

it('marks the field searchable when requested', function () {
    $field = BelongsTo::make('author_id')->related(Author::class)->searchable();

    $extra = $field->toArray()['extra'];
    expect($extra['searchable'])->toBeTrue();
    expect($extra['options'])->toBe([]);
});

it('runs a lookup restricted to the title column by default', function () {
    $field = BelongsTo::make('author_id')->related(Author::class)->searchable();

    $results = $field->runLookup('zel');

    expect($results)->toHaveCount(1);
    expect($results[0]['label'])->toBe('Zelda');
});

it('supports an exact lookup by id to resolve the selected label', function () {
    $arthur = Author::where('name', 'Arthur')->first();

    $field = BelongsTo::make('author_id')->related(Author::class)->searchable();

    $results = $field->runLookup(null, (string) $arthur->id);

    expect($results)->toHaveCount(1);
    expect($results[0]['label'])->toBe('Arthur');
    expect($results[0]['value'])->toBe((string) $arthur->id);
});

it('honours modifyQuery + extra searchColumns during lookup', function () {
    Author::where('name', 'Zelda')->update(['is_active' => false]);

    $field = BelongsTo::make('author_id')
        ->related(Author::class, 'name')
        ->searchColumns(['name'])
        ->modifyQuery(fn ($q) => $q->where('is_active', true));

    $results = $field->runLookup('e');

    expect(array_column($results, 'label'))->toBe(['Beatrice']);
});

it('applies a custom lookup limit', function () {
    $field = BelongsTo::make('author_id')->related(Author::class)->searchable()->lookupLimit(1);

    expect($field->runLookup(''))->toHaveCount(1);
});

it('throws when no related model is configured and validation runs', function () {
    BelongsTo::make('author_id')->getValidationRules();
})->throws(InvalidArgumentException::class);
