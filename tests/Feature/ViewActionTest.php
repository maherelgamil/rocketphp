<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tables\Actions\ViewAction;
use MaherElGamil\Rocket\Tests\Fixtures\OpenWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

it('exposes link action metadata and eye icon', function () {
    $schema = ViewAction::make()->toArray();

    expect($schema)->toMatchArray([
        'name' => 'view',
        'label' => 'View',
        'icon' => 'eye',
        'link' => true,
        'route_suffix' => 'view',
        'ability' => 'view',
        'requires_confirmation' => false,
    ]);
});

it('renders the ViewRecord page through the /view route', function () {
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

    $widget = Widget::create(['name' => 'Alpha']);

    $payload = inertiaGet('/test-admin/widgets/'.$widget->getKey().'/view')->json();

    expect($payload['component'])->toBe('rocket/view-record');
    expect($payload['props']['record']['key'])->toBe($widget->getKey());
    expect($payload['props']['state'])->toBeArray();
    expect($payload['props']['form']['fields'])->toBeArray();
});
