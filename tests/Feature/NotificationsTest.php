<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    Schema::dropIfExists('notifications');
    Schema::create('notifications', function ($table) {
        $table->uuid('id')->primary();
        $table->string('type');
        $table->morphs('notifiable');
        $table->text('data');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });
});

function registerNotificationsPanel(bool $enabled = true): string
{
    $uid = uniqid();
    $path = 'notif-test-'.$uid;

    $panel = Panel::make('notif-'.$uid)
        ->path($path)
        ->authMiddleware([])
        ->resources([WidgetResource::class]);

    if ($enabled) {
        $panel->notificationsEnabled();
    }

    app(PanelManager::class)->register($panel);

    return $path;
}

function insertNotification(int $userId, array $data = [], bool $read = false): string
{
    $id = (string) Str::uuid();
    DB::table('notifications')->insert([
        'id' => $id,
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => GenericUser::class,
        'notifiable_id' => $userId,
        'data' => json_encode(array_merge(['title' => 'Test notification', 'body' => 'Hello world'], $data)),
        'read_at' => $read ? now() : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

it('returns 404 on notifications routes when disabled', function () {
    $path = registerNotificationsPanel(enabled: false);

    test()->getJson("/{$path}/notifications")->assertNotFound();
    // /recent matches the generic {resource}/{record} PUT/PATCH route — 405 is equally inaccessible
    expect(test()->getJson("/{$path}/notifications/recent")->status())->toBeIn([404, 405]);
});

it('includes notifications config in panel shared props', function () {
    $path = registerNotificationsPanel();

    $response = test()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])
        ->get("/{$path}/widgets");

    $notifications = $response->json('props.panel.notifications');
    expect($notifications['enabled'])->toBeTrue();
    expect($notifications['urls']['index'])->toContain('/notifications');
    expect($notifications['urls']['recent'])->toContain('/notifications/recent');
    expect($notifications['urls']['mark_all_read'])->toContain('/notifications/read-all');
});

it('includes unread_count in shared notifications prop', function () {
    $path = registerNotificationsPanel();
    insertNotification(1);
    insertNotification(1);

    $response = test()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])
        ->get("/{$path}/widgets");

    expect($response->json('props.notifications.unread_count'))->toBe(2);
});

it('unread_count excludes already-read notifications', function () {
    $path = registerNotificationsPanel();
    insertNotification(1);
    insertNotification(1, read: true);

    $response = test()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])
        ->get("/{$path}/widgets");

    expect($response->json('props.notifications.unread_count'))->toBe(1);
});

it('does not include notifications shared prop when disabled', function () {
    $path = registerNotificationsPanel(enabled: false);

    $response = test()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])
        ->get("/{$path}/widgets");

    expect($response->json('props.notifications'))->toBeNull();
});

it('recent endpoint returns unread notifications as JSON', function () {
    $path = registerNotificationsPanel();
    insertNotification(1, ['title' => 'Alpha']);
    insertNotification(1, ['title' => 'Beta'], read: true);

    $response = test()->getJson("/{$path}/notifications/recent");

    $response->assertOk();
    $items = $response->json('notifications');
    expect($items)->toHaveCount(1);
    expect($items[0]['title'])->toBe('Alpha');
    expect($items[0]['read_at'])->toBeNull();
});

it('recent endpoint caps results at 10', function () {
    $path = registerNotificationsPanel();
    for ($i = 0; $i < 15; $i++) {
        insertNotification(1);
    }

    $response = test()->getJson("/{$path}/notifications/recent");

    expect($response->json('notifications'))->toHaveCount(10);
});

it('markRead sets read_at on a single notification', function () {
    $path = registerNotificationsPanel();
    $id = insertNotification(1, ['title' => 'To read']);

    $response = test()->postJson("/{$path}/notifications/{$id}/read");

    $response->assertOk();
    expect($response->json('unread_count'))->toBe(0);
    expect(DB::table('notifications')->where('id', $id)->value('read_at'))->not->toBeNull();
});

it('markAllRead zeroes unread count but preserves notification rows', function () {
    $path = registerNotificationsPanel();
    insertNotification(1);
    insertNotification(1);
    $beforeCount = DB::table('notifications')->count();

    $response = test()->postJson("/{$path}/notifications/read-all");

    $response->assertOk();
    expect($response->json('unread_count'))->toBe(0);
    expect(DB::table('notifications')->count())->toBe($beforeCount);
    expect(DB::table('notifications')->whereNull('read_at')->count())->toBe(0);
});

it('users only see their own notifications', function () {
    $path = registerNotificationsPanel();
    insertNotification(1, ['title' => 'Mine']);
    insertNotification(99, ['title' => 'Not mine']);

    $response = test()->getJson("/{$path}/notifications/recent");

    $items = $response->json('notifications');
    expect($items)->toHaveCount(1);
    expect($items[0]['title'])->toBe('Mine');
});

it('notifications index page renders via Inertia', function () {
    $path = registerNotificationsPanel();
    insertNotification(1, ['title' => 'Page notification']);

    $response = test()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])
        ->get("/{$path}/notifications");

    $response->assertOk();
    $response->assertJson(['component' => 'rocket/Notifications']);
    expect($response->json('props.pagination.total'))->toBe(1);
});
