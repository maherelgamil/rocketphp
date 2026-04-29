<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Forms\Components\DatePicker;
use MaherElGamil\Rocket\Forms\Components\TextInput;
use MaherElGamil\Rocket\Tables\Filters\Constraints\BooleanConstraint;
use MaherElGamil\Rocket\Tables\Filters\Constraints\DateConstraint;
use MaherElGamil\Rocket\Tables\Filters\Constraints\NumberConstraint;
use MaherElGamil\Rocket\Tables\Filters\Constraints\SelectConstraint;
use MaherElGamil\Rocket\Tables\Filters\Constraints\TextConstraint;
use MaherElGamil\Rocket\Tables\Filters\CustomFilter;
use MaherElGamil\Rocket\Tables\Filters\FiltersLayout;
use MaherElGamil\Rocket\Tables\Filters\QueryBuilder;
use MaherElGamil\Rocket\Tables\Filters\SelectFilter;
use MaherElGamil\Rocket\Tables\Table;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

beforeEach(function () {
    Schema::dropIfExists('widgets');
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->default('active');
        $table->integer('priority')->default(0);
        $table->boolean('is_featured')->default(false);
        $table->date('published_at')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
});

it('reads SelectFilter via nested query keys', function () {
    $request = Request::create('/widgets', 'GET', ['filters' => ['status' => 'active']]);
    $filter = new SelectFilter('status', 'status', 'Status', ['active' => 'Active', 'archived' => 'Archived']);

    $query = Widget::query();
    $filter->apply($query, $request);

    expect($query->toSql())->toContain('"status" = ?');
    expect($query->getBindings())->toBe(['active']);
});

it('falls back to legacy filter_x query key on SelectFilter', function () {
    $request = Request::create('/widgets', 'GET', ['filter_status' => 'archived']);
    $filter = new SelectFilter('status', 'status', 'Status', ['active' => 'Active']);

    $query = Widget::query();
    $filter->apply($query, $request);

    expect($query->getBindings())->toBe(['archived']);
});

it('emits active_indicators when SelectFilter has a value', function () {
    $request = Request::create('/widgets', 'GET', ['filters' => ['status' => 'active']]);
    $filter = new SelectFilter('status', 'status', 'Status', ['active' => 'Active']);

    $schema = $filter->toSchema($request);

    expect($schema['active_indicators'])->toHaveCount(1);
    expect($schema['active_indicators'][0]['label'])->toBe('Status: Active');
    expect($schema['active_indicators'][0]['clear_keys'])->toContain('filters.status');
    expect($schema['active_indicators'][0]['clear_keys'])->toContain('filter_status');
});

it('persists SelectFilter state across requests when persistInSession is enabled', function () {
    $request1 = Request::create('/widgets', 'GET', ['filters' => ['status' => 'active']]);
    $request1->setLaravelSession(app('session.store'));
    $filter = (new SelectFilter('status', 'status', 'Status', ['active' => 'Active']))->persistInSession();

    $filter->apply(Widget::query(), $request1);

    $request2 = Request::create('/widgets', 'GET');
    $request2->setLaravelSession($request1->session());
    $query2 = Widget::query();
    $filter->apply($query2, $request2);

    expect($query2->getBindings())->toBe(['active']);
});

it('CustomFilter reads form fields and applies query callback', function () {
    $filter = (new CustomFilter('range'))
        ->form([
            DatePicker::make('from'),
            DatePicker::make('until'),
        ])
        ->query(function (Builder $q, array $data): void {
            $q->when($data['from'] ?? null, fn ($q, $v) => $q->whereDate('published_at', '>=', $v));
            $q->when($data['until'] ?? null, fn ($q, $v) => $q->whereDate('published_at', '<=', $v));
        });

    $request = Request::create('/widgets', 'GET', [
        'filters' => ['range' => ['from' => '2026-01-01', 'until' => '2026-12-31']],
    ]);

    $query = Widget::query();
    $filter->apply($query, $request);

    expect($query->getBindings())->toBe(['2026-01-01', '2026-12-31']);
});

it('CustomFilter emits form schema and indicator when active', function () {
    $filter = (new CustomFilter('range'))->label('Date range')->form([
        TextInput::make('from'),
        TextInput::make('until'),
    ]);

    $request = Request::create('/widgets', 'GET', [
        'filters' => ['range' => ['from' => '2026-01-01']],
    ]);

    $schema = $filter->toSchema($request);

    expect($schema['type'])->toBe('custom');
    expect($schema['form'])->toHaveCount(2);
    expect($schema['active_indicators'])->toHaveCount(1);
    expect($schema['active_indicators'][0]['label'])->toBe('Date range');
});

it('Table emits filters_layout in toArray with Dropdown default', function () {
    $table = WidgetResource::table(Table::make(WidgetResource::class));
    $array = $table->toArray();

    expect($array['filters_layout']['layout'])->toBe('dropdown');
    expect($array['filters_layout']['defer'])->toBeTrue();
});

it('Table allows switching FiltersLayout', function () {
    $table = Table::make(WidgetResource::class)->filtersLayout(FiltersLayout::AboveContent);
    expect($table->toArray()['filters_layout']['layout'])->toBe('above_content');
    expect($table->toArray()['filters_layout']['defer'])->toBeFalse();
});

it('TextConstraint applies operators correctly', function () {
    $c = TextConstraint::make('name');

    $q = Widget::query();
    $c->apply($q, 'contains', 'foo');
    expect($q->toSql())->toContain('"name" like ?');
    expect($q->getBindings())->toBe(['%foo%']);

    $q2 = Widget::query();
    $c->apply($q2, 'starts_with', 'bar');
    expect($q2->getBindings())->toBe(['bar%']);
});

it('NumberConstraint applies between operator', function () {
    $c = NumberConstraint::make('priority');
    $q = Widget::query();
    $c->apply($q, 'between', ['from' => 1, 'to' => 5]);

    expect($q->toSql())->toContain('"priority" between');
    expect($q->getBindings())->toBe([1.0, 5.0]);
});

it('DateConstraint applies after operator', function () {
    $c = DateConstraint::make('published_at');
    $q = Widget::query();
    $c->apply($q, 'after', '2026-01-01');

    expect($q->getBindings())->toBe(['2026-01-01']);
});

it('BooleanConstraint applies is_true', function () {
    $c = BooleanConstraint::make('is_featured');
    $q = Widget::query();
    $c->apply($q, 'is_true', null);
    expect($q->getBindings())->toBe([true]);
});

it('SelectConstraint whitelists values against registered options', function () {
    $c = SelectConstraint::make('status')->withOptions(['active' => 'Active', 'archived' => 'Archived']);

    // Inject a value not in the whitelist — should be filtered out.
    $q = Widget::query();
    $c->apply($q, 'in', ['active', 'evil_payload']);

    expect($q->getBindings())->toBe(['active']);
});

it('QueryBuilder applies AND tree of rules', function () {
    $qb = (new QueryBuilder('advanced'))->constraints([
        TextConstraint::make('name'),
        NumberConstraint::make('priority'),
    ]);

    $state = json_encode([
        'logic' => 'and',
        'rules' => [
            ['column' => 'name', 'operator' => 'contains', 'value' => 'foo'],
            ['column' => 'priority', 'operator' => 'gte', 'value' => 5],
        ],
    ]);

    $request = Request::create('/widgets', 'GET', ['filters' => ['advanced' => $state]]);
    $q = Widget::query();
    $qb->apply($q, $request);

    expect($q->getBindings())->toBe(['%foo%', 5.0]);
});

it('QueryBuilder applies nested OR group', function () {
    $qb = (new QueryBuilder('advanced'))->constraints([
        TextConstraint::make('name'),
        TextConstraint::make('status'),
    ]);

    $state = json_encode([
        'logic' => 'and',
        'rules' => [
            ['column' => 'name', 'operator' => 'contains', 'value' => 'foo'],
            ['logic' => 'or', 'rules' => [
                ['column' => 'status', 'operator' => 'equals', 'value' => 'active'],
                ['column' => 'status', 'operator' => 'equals', 'value' => 'archived'],
            ]],
        ],
    ]);

    $request = Request::create('/widgets', 'GET', ['filters' => ['advanced' => $state]]);
    $q = Widget::query();
    $qb->apply($q, $request);

    $sql = $q->toSql();
    expect($sql)->toContain('"name" like ?');
    expect($sql)->toContain('or "status" = ?');
    expect($q->getBindings())->toBe(['%foo%', 'active', 'archived']);
});

it('QueryBuilder ignores unknown columns and operators', function () {
    $qb = (new QueryBuilder('advanced'))->constraints([
        TextConstraint::make('name'),
    ]);

    $state = json_encode([
        'logic' => 'and',
        'rules' => [
            ['column' => 'evil', 'operator' => 'drop_table', 'value' => 'x'],
            ['column' => 'name', 'operator' => 'no_such_op', 'value' => 'x'],
            ['column' => 'name', 'operator' => 'equals', 'value' => 'safe'],
        ],
    ]);

    $request = Request::create('/widgets', 'GET', ['filters' => ['advanced' => $state]]);
    $q = Widget::query();
    $qb->apply($q, $request);

    expect($q->getBindings())->toBe(['safe']);
});

it('QueryBuilder returns rule_count and indicators in schema', function () {
    $qb = (new QueryBuilder('advanced'))->label('Advanced')->constraints([
        TextConstraint::make('name'),
    ]);

    $state = json_encode([
        'logic' => 'and',
        'rules' => [
            ['column' => 'name', 'operator' => 'equals', 'value' => 'x'],
            ['column' => 'name', 'operator' => 'equals', 'value' => 'y'],
        ],
    ]);

    $request = Request::create('/widgets', 'GET', ['filters' => ['advanced' => $state]]);
    $schema = $qb->toSchema($request);

    expect($schema['type'])->toBe('query_builder');
    expect($schema['rule_count'])->toBe(2);
    expect($schema['active_indicators'][0]['label'])->toBe('Advanced (2)');
    expect($schema['constraints'])->toHaveCount(1);
});
