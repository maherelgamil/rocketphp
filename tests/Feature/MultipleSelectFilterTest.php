<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Tables\Filters\SelectFilter;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;

it('defaults to single-select mode in schema', function () {
    $filter = SelectFilter::make('status')->options(['open' => 'Open', 'closed' => 'Closed']);
    $schema = $filter->toSchema(Request::create('/'));

    expect($filter->isMultiple())->toBeFalse();
    expect($schema)->toMatchArray(['multiple' => false, 'value' => null]);
});

it('marks the filter multiple via ->multiple()', function () {
    $filter = SelectFilter::make('status')
        ->options(['open' => 'Open', 'closed' => 'Closed'])
        ->multiple();

    expect($filter->isMultiple())->toBeTrue();

    $schema = $filter->toSchema(Request::create('/'));
    expect($schema)
        ->toMatchArray(['multiple' => true])
        ->and($schema['value'])->toBe([]);
});

it('emits selected values as an array in the schema', function () {
    $request = Request::create('/', 'GET', [
        'filters' => ['status' => ['open', 'closed']],
    ]);

    $filter = SelectFilter::make('status')
        ->options(['open' => 'Open', 'closed' => 'Closed', 'pending' => 'Pending'])
        ->multiple();

    $schema = $filter->toSchema($request);

    expect($schema['value'])->toBe(['open', 'closed']);
});

it('builds a combined indicator label with all selected option names', function () {
    $request = Request::create('/', 'GET', [
        'filters' => ['status' => ['open', 'closed']],
    ]);

    $filter = SelectFilter::make('status')
        ->options(['open' => 'Open', 'closed' => 'Closed'])
        ->multiple();

    $schema = $filter->toSchema($request);

    expect($schema['active_indicators'])->toHaveCount(1);
    expect($schema['active_indicators'][0]['label'])->toBe('Status: Open, Closed');
    expect($schema['active_indicators'][0]['clear_keys'])
        ->toContain('filters.status', 'filter_status');
});

it('applies whereIn when multiple values are selected', function () {
    $request = Request::create('/', 'GET', [
        'filters' => ['status' => ['open', 'pending']],
    ]);

    $filter = SelectFilter::make('status')
        ->options(['open' => 'Open', 'closed' => 'Closed', 'pending' => 'Pending'])
        ->multiple();

    /** @var Builder<Widget> $query */
    $query = Widget::query();
    $filter->apply($query, $request);

    $sql = $query->toRawSql();
    expect($sql)->toContain('"status" in (\'open\', \'pending\')');
});

it('applies no constraint when no values selected in multiple mode', function () {
    $filter = SelectFilter::make('status')
        ->options(['open' => 'Open'])
        ->multiple();

    /** @var Builder<Widget> $query */
    $query = Widget::query();
    $filter->apply($query, Request::create('/'));

    expect($query->toRawSql())->not->toContain('"status"');
});

it('coerces a single non-array value into a one-element array in multiple mode', function () {
    $request = Request::create('/', 'GET', [
        'filters' => ['status' => 'open'],
    ]);

    $filter = SelectFilter::make('status')
        ->options(['open' => 'Open', 'closed' => 'Closed'])
        ->multiple();

    $schema = $filter->toSchema($request);
    expect($schema['value'])->toBe(['open']);

    /** @var Builder<Widget> $query */
    $query = Widget::query();
    $filter->apply($query, $request);
    expect($query->toRawSql())->toContain('"status" in (\'open\')');
});
