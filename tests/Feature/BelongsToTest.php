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

    expect($field->toArray()['extra']['searchable'])->toBeTrue();
});

it('throws when no related model is configured and validation runs', function () {
    BelongsTo::make('author_id')->getValidationRules();
})->throws(InvalidArgumentException::class);
