<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Dashboard\ChartWidget;
use MaherElGamil\Rocket\Support\Color;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;

beforeEach(function () {
    Schema::dropIfExists('widgets');
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->default('active');
        $table->boolean('is_featured')->default(false);
        $table->date('published_at')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
});

it('returns the correct type and title', function () {
    $widget = ChartWidget::make('Revenue')->data(fn () => Widget::query());
    $arr = $widget->toArray();

    expect($arr['type'])->toBe('chart');
    expect($arr['title'])->toBe('Revenue');
});

it('serializes chart_type and color', function () {
    $arr = ChartWidget::make('Stats')
        ->type('bar')
        ->color(Color::Green)
        ->data(fn () => Widget::query())
        ->toArray();

    expect($arr['chart_type'])->toBe('bar');
    expect($arr['color'])->toBe(Color::Green->hex());
});

it('returns empty data when no callback set', function () {
    $arr = ChartWidget::make('Empty')->toArray();

    expect($arr['data'])->toBe([]);
});

it('returns the correct number of day buckets', function () {
    $arr = ChartWidget::make('Daily')
        ->interval('day')
        ->limit(7)
        ->data(fn () => Widget::query())
        ->toArray();

    expect($arr['data'])->toHaveCount(7);
});

it('returns the correct number of month buckets', function () {
    $arr = ChartWidget::make('Monthly')
        ->interval('month')
        ->limit(6)
        ->data(fn () => Widget::query())
        ->toArray();

    expect($arr['data'])->toHaveCount(6);
});

it('counts records in the correct bucket', function () {
    Widget::create(['name' => 'Today widget', 'created_at' => now()]);
    Widget::create(['name' => 'Another today widget', 'created_at' => now()]);

    $arr = ChartWidget::make('Count')
        ->interval('day')
        ->limit(7)
        ->data(fn () => Widget::query())
        ->toArray();

    // Last bucket (today) should have 2 records
    $last = end($arr['data']);
    expect($last['value'])->toBe(2.0);
});

it('fills missing buckets with zero', function () {
    // Only insert a record for today
    Widget::create(['name' => 'Only today', 'created_at' => now()]);

    $arr = ChartWidget::make('Gaps')
        ->interval('day')
        ->limit(7)
        ->data(fn () => Widget::query())
        ->toArray();

    $values = array_column($arr['data'], 'value');
    // 6 zeros + 1 for today
    expect(array_sum($values))->toBe(1.0);
    expect(count(array_filter($values, fn ($v) => $v === 0.0)))->toBe(6);
});

it('sums a value column when specified', function () {
    Widget::create(['name' => 'A', 'created_at' => now()]);
    Widget::create(['name' => 'B', 'created_at' => now()]);

    $arr = ChartWidget::make('IDs')
        ->interval('day')
        ->limit(1)
        ->valueColumn('id')
        ->data(fn () => Widget::query())
        ->toArray();

    // SUM of ids 1+2 = 3
    expect($arr['data'][0]['value'])->toBe(3.0);
});

it('accepts a hex color string', function () {
    $arr = ChartWidget::make('Hex')->color('#ff0000')->data(fn () => Widget::query())->toArray();

    expect($arr['color'])->toBe('#ff0000');
});
