<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tests\Fixtures\DenyViewAnyWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\OpenWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

beforeEach(function () {
    test()->actingAs(new GenericUser(['id' => 1]));

    Schema::dropIfExists('widgets');
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name');
        $table->softDeletes();
    });

    app(PanelManager::class)->register(
        Panel::make('test')
            ->path('test-admin')
            ->authMiddleware([])
            ->resources([WidgetResource::class])
    );
});

it('renders a 404 Inertia error page for unknown resource slugs', function () {
    Gate::policy(Widget::class, OpenWidgetPolicy::class);

    $response = test()
        ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])
        ->get('/test-admin/nope');

    $response->assertStatus(404);
    expect($response->headers->get('X-Inertia'))->toBe('true');
    expect($response->json('component'))->toBe('rocket/error');
    expect($response->json('props.status'))->toBe(404);
});

it('renders a 403 Inertia error page when a policy denies access', function () {
    Gate::policy(Widget::class, DenyViewAnyWidgetPolicy::class);

    $response = test()
        ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])
        ->get('/test-admin/widgets');

    $response->assertStatus(403);
    expect($response->json('component'))->toBe('rocket/error');
    expect($response->json('props.status'))->toBe(403);
});
