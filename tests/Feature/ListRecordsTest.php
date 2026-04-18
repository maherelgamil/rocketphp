<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tables\Table;
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

    Widget::query()->insert([
        ['name' => 'Alpha', 'status' => 'active', 'is_featured' => 0, 'published_at' => '2026-01-10'],
        ['name' => 'Beta', 'status' => 'draft', 'is_featured' => 1, 'published_at' => '2026-02-15'],
        ['name' => 'Gamma', 'status' => 'active', 'is_featured' => 0, 'published_at' => '2026-03-01'],
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

it('filters records by select filter query param', function () {
    $payload = inertiaGet('/test-admin/widgets?filter_status=draft')->json();

    expect($payload['props']['records'])->toHaveCount(1)
        ->and($payload['props']['records'][0]['name'])->toBe('Beta');
});

it('excludes soft-deleted rows by default', function () {
    Widget::query()->where('name', 'Alpha')->delete();

    $payload = inertiaGet('/test-admin/widgets')->json();

    expect($payload['props']['records'])->toHaveCount(2);
});

it('lists only trashed rows when filter_trashed is only', function () {
    Widget::query()->where('name', 'Alpha')->delete();

    $payload = inertiaGet('/test-admin/widgets?filter_trashed=only')->json();

    expect($payload['props']['records'])->toHaveCount(1)
        ->and($payload['props']['records'][0]['name'])->toBe('Alpha');
});

it('filters by ternary boolean column', function () {
    $payload = inertiaGet('/test-admin/widgets?filter_featured=yes')->json();

    expect($payload['props']['records'])->toHaveCount(1)
        ->and($payload['props']['records'][0]['name'])->toBe('Beta');
});

it('filters by published date range', function () {
    $payload = inertiaGet('/test-admin/widgets?filter_published_from=2026-02-01&filter_published_until=2026-02-28')->json();

    expect($payload['props']['records'])->toHaveCount(1)
        ->and($payload['props']['records'][0]['name'])->toBe('Beta');
});
