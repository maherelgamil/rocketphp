<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Support\Color;
use MaherElGamil\Rocket\Support\Density;
use MaherElGamil\Rocket\Support\Font;
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

function registerThemedPanel(callable $configure): string
{
    $uid = uniqid();
    $path = 'theme-test-'.$uid;

    $panel = Panel::make('theme-'.$uid)
        ->path($path)
        ->authMiddleware([])
        ->resources([WidgetResource::class]);

    $configure($panel);

    app(PanelManager::class)->register($panel);

    return $path;
}

function panelTheme(string $path): ?array
{
    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/widgets");

    return $response->json('props.panel.theme');
}

it('includes an empty theme by default', function () {
    $path = registerThemedPanel(fn () => null);

    expect(panelTheme($path))->toBe([]);
});

it('setPrimaryColor accepts a raw HSL string', function () {
    $path = registerThemedPanel(fn (Panel $p) => $p->setPrimaryColor('262 80% 50%'));

    expect(panelTheme($path)['primary'])->toBe('262 80% 50%');
});

it('setPrimaryColor accepts a Color enum', function () {
    $path = registerThemedPanel(fn (Panel $p) => $p->setPrimaryColor(Color::Indigo));

    expect(panelTheme($path)['primary'])->toBe(Color::Indigo->hsl());
});

it('setAccentColor accepts a Color enum', function () {
    $path = registerThemedPanel(fn (Panel $p) => $p->setAccentColor(Color::Teal));

    expect(panelTheme($path)['accent'])->toBe(Color::Teal->hsl());
});

it('setColor sets an arbitrary named color token', function () {
    $path = registerThemedPanel(fn (Panel $p) => $p->setColor('primary', Color::Blue));

    expect(panelTheme($path)['primary'])->toBe(Color::Blue->hsl());
});

it('setFont serializes the font family', function () {
    $path = registerThemedPanel(fn (Panel $p) => $p->setFont('Inter'));

    expect(panelTheme($path)['font'])->toBe('Inter');
});

it('setRadius serializes the radius value', function () {
    $path = registerThemedPanel(fn (Panel $p) => $p->setRadius('0rem'));

    expect(panelTheme($path)['radius'])->toBe('0rem');
});

it('setDensity accepts a raw string', function () {
    $path = registerThemedPanel(fn (Panel $p) => $p->setDensity('compact'));

    expect(panelTheme($path)['density'])->toBe('compact');
});

it('setDensity accepts a Density enum', function () {
    $path = registerThemedPanel(fn (Panel $p) => $p->setDensity(Density::Comfortable));

    expect(panelTheme($path)['density'])->toBe('comfortable');
});

it('setFont accepts a Font enum', function () {
    $path = registerThemedPanel(fn (Panel $p) => $p->setFont(Font::Geist));

    expect(panelTheme($path)['font'])->toBe('Geist');
});

it('all setters are chainable and compose correctly', function () {
    $path = registerThemedPanel(fn (Panel $p) => $p
        ->setPrimaryColor(Color::Purple)
        ->setAccentColor(Color::Pink)
        ->setFont('Geist')
        ->setRadius('0.5rem')
        ->setDensity('comfortable')
    );

    $theme = panelTheme($path);
    expect($theme['primary'])->toBe(Color::Purple->hsl());
    expect($theme['accent'])->toBe(Color::Pink->hsl());
    expect($theme['font'])->toBe('Geist');
    expect($theme['radius'])->toBe('0.5rem');
    expect($theme['density'])->toBe('comfortable');
});

it('Color enum hsl() returns a non-empty HSL string for every case', function (Color $color) {
    expect($color->hsl())->toMatch('/^\d{1,3} \d{1,3}% \d{1,3}%$/');
})->with(Color::cases());
