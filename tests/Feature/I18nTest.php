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
        $table->softDeletes();
    });
});

function registerI18nPanel(callable $configure): array
{
    $uid = uniqid();
    $id = 'i18n-'.$uid;
    $path = 'i18n-test-'.$uid;

    $panel = Panel::make($id)
        ->path($path)
        ->authMiddleware([])
        ->resources([WidgetResource::class]);

    $configure($panel);

    app(PanelManager::class)->register($panel);

    return ['id' => $id, 'path' => $path, 'panel' => $panel];
}

function panelSharedProps(string $path): array
{
    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/widgets");

    return $response->json('props.rocket.panel') ?? [];
}

it('panel locale defaults to app locale', function () {
    app()->setLocale('en');
    $panel = Panel::make('default-locale-panel');

    expect($panel->getLocale())->toBe('en');

    app()->setLocale('fr');
    expect($panel->getLocale())->toBe('fr');
});

it('panel locale can be overridden with ->locale()', function () {
    $panel = Panel::make('override-locale-panel')->locale('ar');

    expect($panel->getLocale())->toBe('ar');
});

it('panel available locales can be set with ->availableLocales()', function () {
    $panel = Panel::make('available-locales-panel')->availableLocales(['en', 'ar', 'fr']);

    expect($panel->getAvailableLocales())->toBe(['en', 'ar', 'fr']);
});

it('toSharedProps includes locale, available_locales, and translations keys', function () {
    ['path' => $path] = registerI18nPanel(fn (Panel $p) => $p
        ->locale('en')
        ->availableLocales(['en', 'ar'])
    );

    $props = panelSharedProps($path);

    expect($props)->toHaveKey('locale');
    expect($props)->toHaveKey('available_locales');
    expect($props)->toHaveKey('translations');
    expect($props['locale'])->toBe('en');
    expect($props['available_locales'])->toBe(['en', 'ar']);
    expect($props['translations'])->toBeArray();
});

it('arabic locale loads correct translations map from lang/ar.json', function () {
    ['path' => $path] = registerI18nPanel(fn (Panel $p) => $p
        ->locale('ar')
        ->availableLocales(['en', 'ar'])
    );

    $props = panelSharedProps($path);

    expect($props['translations'])->toHaveKey('Search...');
    expect($props['translations']['Search...'])->toBe('بحث...');
    expect($props['translations'])->toHaveKey('No records found.');
});

it('unknown locale returns empty translations map', function () {
    ['path' => $path] = registerI18nPanel(fn (Panel $p) => $p
        ->locale('zz')
        ->availableLocales(['en', 'zz'])
    );

    $props = panelSharedProps($path);

    expect($props['translations'])->toBe([]);
});

it('locale switcher rejects locales not in availableLocales', function () {
    ['path' => $path] = registerI18nPanel(fn (Panel $p) => $p
        ->locale('en')
        ->availableLocales(['en', 'ar'])
    );

    test()->post("/{$path}/locale", ['locale' => 'fr'])
        ->assertStatus(422);
});

it('locale switcher stores locale in session', function () {
    ['id' => $id, 'path' => $path] = registerI18nPanel(fn (Panel $p) => $p
        ->locale('en')
        ->availableLocales(['en', 'ar'])
    );

    test()->post("/{$path}/locale", ['locale' => 'ar']);

    expect(session('rocket_locale_'.$id))->toBe('ar');
});
