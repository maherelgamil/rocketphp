<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tests\Fixtures\DenyViewAnyWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\OpenWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\SearchableWidgetResource;
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
});

function registerSearchPanel(array $resources, bool $globalSearch = true): string
{
    $uid = uniqid();
    $path = 'search-test-'.$uid;

    $panel = Panel::make('search-'.$uid)
        ->path($path)
        ->authMiddleware([])
        ->resources($resources);

    if (! $globalSearch) {
        $panel->globalSearchEnabled(false);
    }

    app(PanelManager::class)->register($panel);

    return $path;
}

it('returns empty results for a blank query', function () {
    $path = registerSearchPanel([SearchableWidgetResource::class]);
    Widget::create(['name' => 'Alpha Widget']);

    $response = test()->getJson("/{$path}/search?q=");

    $response->assertOk()->assertJson(['results' => []]);
});

it('returns matching records for a query', function () {
    $path = registerSearchPanel([SearchableWidgetResource::class]);
    Widget::create(['name' => 'Alpha Widget']);
    Widget::create(['name' => 'Beta Gadget']);

    $response = test()->getJson("/{$path}/search?q=Alpha");

    $response->assertOk();
    $results = $response->json('results');
    expect($results)->toHaveCount(1);
    expect($results[0]['resource']['label'])->toBe('Widgets');
    expect($results[0]['items'][0]['title'])->toBe('Alpha Widget');
    expect($results[0]['items'][0]['url'])->toContain('/widgets/');
});

it('excludes resources where user cannot viewAny', function () {
    Gate::policy(Widget::class, DenyViewAnyWidgetPolicy::class);
    $path = registerSearchPanel([SearchableWidgetResource::class]);
    Widget::create(['name' => 'Alpha Widget']);

    $response = test()->getJson("/{$path}/search?q=Alpha");

    $response->assertOk()->assertJson(['results' => []]);
});

it('excludes resources that declare no globalSearchColumns', function () {
    $path = registerSearchPanel([WidgetResource::class]);
    Widget::create(['name' => 'Alpha Widget']);

    $response = test()->getJson("/{$path}/search?q=Alpha");

    $response->assertOk()->assertJson(['results' => []]);
});

it('caps results at 5 per resource', function () {
    $path = registerSearchPanel([SearchableWidgetResource::class]);

    for ($i = 1; $i <= 10; $i++) {
        Widget::create(['name' => "Widget {$i}"]);
    }

    $response = test()->getJson("/{$path}/search?q=Widget");

    $response->assertOk();
    $items = $response->json('results.0.items');
    expect($items)->toHaveCount(5);
});

it('returns 404 when global search is disabled', function () {
    $path = registerSearchPanel([SearchableWidgetResource::class], globalSearch: false);

    test()->getJson("/{$path}/search?q=Alpha")->assertNotFound();
});

it('returns the edit url in search results when resource has edit page', function () {
    $path = registerSearchPanel([SearchableWidgetResource::class]);
    $widget = Widget::create(['name' => 'Alpha Widget']);

    $response = test()->getJson("/{$path}/search?q=Alpha");

    $url = $response->json('results.0.items.0.url');
    expect($url)->toBe("/{$path}/widgets/{$widget->getKey()}/edit");
});

it('includes global_search in panel shared props', function () {
    $path = registerSearchPanel([SearchableWidgetResource::class]);

    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/dashboard");

    $panel = $response->json('props.panel');
    expect($panel)->toHaveKey('global_search');
    expect($panel['global_search']['enabled'])->toBeTrue();
    expect($panel['global_search']['url'])->toContain('/search');
});
