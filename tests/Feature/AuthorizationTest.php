<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tests\Fixtures\DenyCreateWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\DenyViewAnyWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\OpenWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

beforeEach(function () {
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

it('returns 403 when viewAny is denied for the list page', function () {
    Gate::policy(Widget::class, DenyViewAnyWidgetPolicy::class);
    test()->actingAs(new GenericUser(['id' => 1]));

    test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get('/test-admin/widgets')->assertForbidden();
});

it('returns 403 when create is denied for store', function () {
    Gate::policy(Widget::class, DenyCreateWidgetPolicy::class);
    test()->actingAs(new GenericUser(['id' => 1]));

    test()->post('/test-admin/widgets', [
        'name' => 'N',
        'status' => 'active',
    ])->assertForbidden();
});

it('allows list when policy permits', function () {
    Gate::policy(Widget::class, OpenWidgetPolicy::class);
    test()->actingAs(new GenericUser(['id' => 1]));

    test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get('/test-admin/widgets')->assertOk();
});
