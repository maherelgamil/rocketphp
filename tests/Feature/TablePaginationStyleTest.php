<?php

declare(strict_types=1);

use MaherElGamil\Rocket\Tables\Table;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

it('defaults pagination_style to the configured value', function () {
    config()->set('rocket.pagination.style', 'numbered');

    $schema = Table::make(WidgetResource::class)->toArray();

    expect($schema['pagination_style'])->toBe('numbered');
});

it('falls back to numbered when config value is invalid', function () {
    config()->set('rocket.pagination.style', 'bogus');

    $schema = Table::make(WidgetResource::class)->toArray();

    expect($schema['pagination_style'])->toBe('numbered');
});

it('honors a per-table paginationStyle override', function () {
    config()->set('rocket.pagination.style', 'numbered');

    $schema = Table::make(WidgetResource::class)
        ->paginationStyle('compact')
        ->toArray();

    expect($schema['pagination_style'])->toBe('compact');
});

it('ignores invalid override and falls back to config', function () {
    config()->set('rocket.pagination.style', 'simple');

    $schema = Table::make(WidgetResource::class)
        ->paginationStyle('not-a-style')
        ->toArray();

    expect($schema['pagination_style'])->toBe('simple');
});
