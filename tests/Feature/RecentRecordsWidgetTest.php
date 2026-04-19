<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Dashboard\RecentRecordsWidget;
use MaherElGamil\Rocket\Tests\Fixtures\OpenWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

beforeEach(function () {
    Gate::policy(Widget::class, OpenWidgetPolicy::class);

    Schema::dropIfExists('widgets');
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->default('active');
        $table->boolean('is_featured')->default(false);
        $table->date('published_at')->nullable();
        $table->softDeletes();
    });
});

it('returns correct type and title', function () {
    $arr = RecentRecordsWidget::make('Recent Widgets')->resource(WidgetResource::class)->toArray();

    expect($arr['type'])->toBe('recent_records');
    expect($arr['title'])->toBe('Recent Widgets');
});

it('returns columns from the resource table schema', function () {
    $arr = RecentRecordsWidget::make('Widgets')->resource(WidgetResource::class)->toArray();

    $names = array_column($arr['columns'], 'name');
    expect($names)->toContain('name');
});

it('respects the limit', function () {
    for ($i = 1; $i <= 10; $i++) {
        Widget::create(['name' => "Widget {$i}"]);
    }

    $arr = RecentRecordsWidget::make('Widgets')->resource(WidgetResource::class)->limit(3)->toArray();

    expect($arr['rows'])->toHaveCount(3);
});

it('returns empty rows when no records exist', function () {
    $arr = RecentRecordsWidget::make('Widgets')->resource(WidgetResource::class)->toArray();

    expect($arr['rows'])->toBe([]);
});

it('includes resource_url', function () {
    $arr = RecentRecordsWidget::make('Widgets')->resource(WidgetResource::class)->toArray();

    expect($arr['resource_url'])->toBe('widgets');
});

it('returns empty state when no resource set', function () {
    $arr = RecentRecordsWidget::make('Empty')->toArray();

    expect($arr['columns'])->toBe([]);
    expect($arr['rows'])->toBe([]);
    expect($arr['resource_url'])->toBeNull();
});
