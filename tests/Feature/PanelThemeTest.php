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
});

function registerThemedPanel(array $theme = []): string
{
    $uid = uniqid();
    $path = 'theme-test-'.$uid;

    $panel = Panel::make('theme-'.$uid)
        ->path($path)
        ->authMiddleware([])
        ->resources([WidgetResource::class]);

    if ($theme !== []) {
        $panel->theme($theme);
    }

    app(PanelManager::class)->register($panel);

    return $path;
}

it('includes an empty theme in shared props by default', function () {
    $path = registerThemedPanel();

    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/widgets");

    $panel = $response->json('props.panel');
    expect($panel)->toHaveKey('theme');
    expect($panel['theme'])->toBe([]);
});

it('serializes custom theme tokens into shared props', function () {
    $path = registerThemedPanel([
        'primary' => '262 80% 50%',
        'radius' => '0.75rem',
        'density' => 'compact',
        'font' => 'Inter',
    ]);

    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/widgets");

    $theme = $response->json('props.panel.theme');
    expect($theme['primary'])->toBe('262 80% 50%');
    expect($theme['radius'])->toBe('0.75rem');
    expect($theme['density'])->toBe('compact');
    expect($theme['font'])->toBe('Inter');
});

it('allows partial theme tokens', function () {
    $path = registerThemedPanel(['radius' => '0rem']);

    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/widgets");

    $theme = $response->json('props.panel.theme');
    expect($theme)->toBe(['radius' => '0rem']);
    expect($theme)->not->toHaveKey('primary');
});

it('returns fluent self from theme() for method chaining', function () {
    $panel = Panel::make('chain-test');
    expect($panel->theme(['radius' => '1rem']))->toBeInstanceOf(Panel::class);
});
