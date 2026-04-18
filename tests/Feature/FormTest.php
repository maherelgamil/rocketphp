<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

beforeEach(function () {
    Schema::dropIfExists('widgets');
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('status')->default('active');
    });

    app(PanelManager::class)->register(
        Panel::make('test')
            ->path('test-admin')
            ->authMiddleware([])
            ->resources([WidgetResource::class])
    );
});

function rocketInertia(string $uri): TestResponse
{
    return test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get($uri);
}

it('renders the create page with the form schema', function () {
    $payload = rocketInertia('/test-admin/widgets/create')->json();

    expect($payload['component'])->toBe('rocket/CreateRecord')
        ->and($payload['props']['form']['fields'])->toHaveCount(3)
        ->and($payload['props']['form']['fields'][0]['type'])->toBe('text')
        ->and($payload['props']['form']['fields'][0]['name'])->toBe('name')
        ->and($payload['props']['form']['fields'][0]['required'])->toBeTrue()
        ->and($payload['props']['form']['fields'][2]['type'])->toBe('select');
});

it('stores a new record via the resource store endpoint', function () {
    $response = test()->post('/test-admin/widgets', [
        'name' => 'Alpha',
        'description' => 'first widget',
        'status' => 'active',
    ]);

    $response->assertRedirect('/test-admin/widgets');
    expect(Widget::query()->where('name', 'Alpha')->exists())->toBeTrue();
});

it('rejects invalid store submissions with validation errors', function () {
    $response = test()->post('/test-admin/widgets', [
        'name' => '',
        'status' => 'unknown-status',
    ]);

    $response->assertInvalid(['name', 'status']);
    expect(Widget::query()->count())->toBe(0);
});

it('renders the edit page with populated state', function () {
    $widget = Widget::query()->create([
        'name' => 'Beta',
        'description' => 'the second one',
        'status' => 'draft',
    ]);

    $payload = rocketInertia("/test-admin/widgets/{$widget->id}/edit")->json();

    expect($payload['component'])->toBe('rocket/EditRecord')
        ->and($payload['props']['state']['name'])->toBe('Beta')
        ->and($payload['props']['state']['status'])->toBe('draft')
        ->and($payload['props']['action']['method'])->toBe('patch');
});

it('updates a record via the resource update endpoint', function () {
    $widget = Widget::query()->create([
        'name' => 'Gamma',
        'status' => 'draft',
    ]);

    $response = test()->patch("/test-admin/widgets/{$widget->id}", [
        'name' => 'Gamma renamed',
        'status' => 'active',
    ]);

    $response->assertRedirect('/test-admin/widgets');
    expect($widget->fresh()->name)->toBe('Gamma renamed')
        ->and($widget->fresh()->status)->toBe('active');
});

it('deletes a record via the resource destroy endpoint', function () {
    $widget = Widget::query()->create([
        'name' => 'Delta',
        'status' => 'active',
    ]);

    $response = test()->delete("/test-admin/widgets/{$widget->id}");

    $response->assertRedirect('/test-admin/widgets');
    expect(Widget::query()->find($widget->id))->toBeNull();
});
