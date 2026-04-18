<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tests\Fixtures\OpenWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

beforeEach(function () {
    Gate::policy(Widget::class, OpenWidgetPolicy::class);
    test()->actingAs(new GenericUser(['id' => 1]));

    Schema::dropIfExists('widgets');
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->default('active');
        $table->boolean('is_featured')->default(false);
        $table->date('published_at')->nullable();
        $table->softDeletes();
    });

    app(PanelManager::class)->register(
        Panel::make('test')
            ->path('test-admin')
            ->authMiddleware([])
            ->resources([WidgetResource::class])
    );
});

it('deletes a record via row action endpoint', function () {
    $w = Widget::query()->create(['name' => 'RowDel', 'status' => 'active']);

    test()
        ->post("/test-admin/widgets/{$w->id}/actions/delete", [])
        ->assertRedirect('/test-admin/widgets');

    expect(Widget::query()->find($w->id))->toBeNull();
});

it('bulk deletes records', function () {
    $a = Widget::query()->create(['name' => 'A', 'status' => 'active']);
    $b = Widget::query()->create(['name' => 'B', 'status' => 'active']);

    test()
        ->post('/test-admin/widgets/bulk-actions/bulk-delete', [
            'ids' => [$a->id, $b->id],
        ])
        ->assertRedirect('/test-admin/widgets');

    expect(Widget::query()->count())->toBe(0);
});
