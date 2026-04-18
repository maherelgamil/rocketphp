<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tables\Table;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

beforeEach(function () {
    Schema::dropIfExists('widgets');
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->default('active');
    });

    Widget::query()->insert([
        ['name' => 'Alpha', 'status' => 'active'],
        ['name' => 'Beta', 'status' => 'draft'],
        ['name' => 'Gamma', 'status' => 'active'],
    ]);

    app(PanelManager::class)->register(
        Panel::make('test')
            ->path('test-admin')
            ->authMiddleware([])
            ->resources([WidgetResource::class])
    );
});

function inertiaGet(string $uri): TestResponse
{
    return test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get($uri);
}

it('exposes a table schema from the resource', function () {
    $schema = WidgetResource::table(Table::make(WidgetResource::class))->toArray();

    expect($schema['columns'])->toHaveCount(3)
        ->and($schema['columns'][0]['name'])->toBe('id')
        ->and($schema['columns'][2]['type'])->toBe('badge')
        ->and($schema['searchable'])->toBeTrue();
});

it('renders the list records page for a registered resource', function () {
    $response = inertiaGet('/test-admin/widgets')->assertOk();
    $payload = $response->json();

    expect($payload['component'])->toBe('rocket/ListRecords')
        ->and($payload['props']['resource']['slug'])->toBe('widgets')
        ->and($payload['props']['records'])->toHaveCount(3)
        ->and($payload['props']['pagination']['total'])->toBe(3);
});

it('filters records via the search query', function () {
    $payload = inertiaGet('/test-admin/widgets?search=Beta')->json();

    expect($payload['props']['records'])->toHaveCount(1)
        ->and($payload['props']['records'][0]['name'])->toBe('Beta');
});

it('sorts records by the requested column', function () {
    $payload = inertiaGet('/test-admin/widgets?sort=name&direction=desc')->json();

    expect(array_column($payload['props']['records'], 'name'))->toBe(['Gamma', 'Beta', 'Alpha']);
});
