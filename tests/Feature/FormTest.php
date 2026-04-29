<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
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
        $table->text('description')->nullable();
        $table->string('status')->default('active');
        $table->string('avatar')->nullable();
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

    expect($payload['component'])->toBe('rocket/create-record')
        ->and($payload['props']['form']['fields'])->toHaveCount(4)
        ->and($payload['props']['form']['fields'][0]['type'])->toBe('text')
        ->and($payload['props']['form']['fields'][0]['name'])->toBe('name')
        ->and($payload['props']['form']['fields'][0]['required'])->toBeTrue()
        ->and($payload['props']['form']['fields'][2]['type'])->toBe('select')
        ->and($payload['props']['form']['fields'][3]['type'])->toBe('file')
        ->and($payload['props']['form']['fields'][3]['extra']['image'])->toBeTrue();
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

    expect($payload['component'])->toBe('rocket/edit-record')
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

it('uploads a file and stores its path on create', function () {
    Storage::fake('public');

    $response = test()->post('/test-admin/widgets', [
        'name' => 'Epsilon',
        'status' => 'active',
        'avatar' => UploadedFile::fake()->image('avatar.png'),
    ]);

    $response->assertRedirect('/test-admin/widgets');
    $widget = Widget::query()->where('name', 'Epsilon')->firstOrFail();
    expect($widget->avatar)->toStartWith('widgets/');
    Storage::disk('public')->assertExists($widget->avatar);
});

it('preserves the existing file path on update when no new file is uploaded', function () {
    Storage::fake('public');

    $widget = Widget::query()->create([
        'name' => 'Zeta',
        'status' => 'active',
        'avatar' => 'widgets/existing.png',
    ]);

    $response = test()->patch("/test-admin/widgets/{$widget->id}", [
        'name' => 'Zeta renamed',
        'status' => 'active',
    ]);

    $response->assertRedirect('/test-admin/widgets');
    expect($widget->fresh()->avatar)->toBe('widgets/existing.png');
});

it('replaces the stored file when a new one is uploaded on update', function () {
    Storage::fake('public');

    $widget = Widget::query()->create([
        'name' => 'Eta',
        'status' => 'active',
        'avatar' => 'widgets/old.png',
    ]);

    $response = test()->patch("/test-admin/widgets/{$widget->id}", [
        'name' => 'Eta',
        'status' => 'active',
        'avatar' => UploadedFile::fake()->image('new.png'),
    ]);

    $response->assertRedirect('/test-admin/widgets');
    expect($widget->fresh()->avatar)
        ->toStartWith('widgets/')
        ->not->toBe('widgets/old.png');
});

it('exposes the current file path via form schema on edit', function () {
    $widget = Widget::query()->create([
        'name' => 'Theta',
        'status' => 'active',
        'avatar' => 'widgets/theta.png',
    ]);

    $payload = rocketInertia("/test-admin/widgets/{$widget->id}/edit")->json();

    $fileField = collect($payload['props']['form']['fields'])
        ->firstWhere('name', 'avatar');

    expect($fileField['extra']['current'])->toBe('widgets/theta.png')
        ->and($payload['props']['state']['avatar'])->toBeNull();
});

it('rejects non-image uploads for image-only file fields', function () {
    Storage::fake('public');

    $response = test()->post('/test-admin/widgets', [
        'name' => 'Iota',
        'status' => 'active',
        'avatar' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
    ]);

    $response->assertInvalid(['avatar']);
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
