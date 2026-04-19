<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Dashboard\StatWidget;
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
            ->widgets([
                new StatWidget('Total widgets', 0),
            ])
            ->resources([WidgetResource::class])
    );
});

it('renders the dashboard page with widgets', function () {
    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get('/test-admin/dashboard');

    $response->assertOk();
    $payload = $response->json();

    expect($payload['component'])->toBe('rocket/Dashboard')
        ->and($payload['props']['content'])->toHaveCount(1)
        ->and($payload['props']['content'][0]['type'])->toBe('widget')
        ->and($payload['props']['content'][0]['widget']['type'])->toBe('stat')
        ->and($payload['props']['content'][0]['widget']['label'])->toBe('Total widgets');
});
