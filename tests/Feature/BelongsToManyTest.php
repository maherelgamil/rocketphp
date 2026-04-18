<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Forms\Components\BelongsToMany;
use MaherElGamil\Rocket\Forms\Components\Field;
use MaherElGamil\Rocket\Tests\Fixtures\Tag;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;

beforeEach(function () {
    Schema::dropIfExists('widget_tag');
    Schema::dropIfExists('tags');
    Schema::dropIfExists('widgets');

    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name')->nullable();
        $table->softDeletes();
    });
    Schema::create('tags', function ($table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('widget_tag', function ($table) {
        $table->foreignId('widget_id');
        $table->foreignId('tag_id');
    });

    Tag::insert([
        ['id' => 1, 'name' => 'Alpha'],
        ['id' => 2, 'name' => 'Beta'],
        ['id' => 3, 'name' => 'Gamma'],
    ]);
});

it('loads options from the related model sorted by the title column', function () {
    $field = BelongsToMany::make('tags')->related(Tag::class, 'name');

    $options = $field->toArray()['extra']['options'];

    expect(array_values($options))->toBe(['Alpha', 'Beta', 'Gamma']);
});

it('returns Field::SKIP from processSubmission so the relation key is not written to the model', function () {
    $field = BelongsToMany::make('tags')->related(Tag::class);
    $widget = Widget::create(['name' => 'w1']);

    $result = $field->processSubmission(request(), [1, 2], $widget);

    expect($result)->toBe(Field::SKIP);
});

it('syncs pivot rows on afterSave', function () {
    $field = BelongsToMany::make('tags')->related(Tag::class);
    $widget = Widget::create(['name' => 'w1']);

    $field->afterSave($widget, ['1', '2']);

    expect($widget->tags()->pluck('tags.id')->all())->toEqualCanonicalizing([1, 2]);

    // second sync replaces the set
    $field->afterSave($widget, ['3']);
    expect($widget->tags()->pluck('tags.id')->all())->toEqualCanonicalizing([3]);
});

it('reads the current pivot ids as the field state', function () {
    $field = BelongsToMany::make('tags')->related(Tag::class);
    $widget = Widget::create(['name' => 'w1']);
    $widget->tags()->sync([1, 3]);

    expect($field->getState($widget->fresh()))->toEqualCanonicalizing(['1', '3']);
});
