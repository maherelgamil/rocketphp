<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tests\Fixtures\User;

beforeEach(function () {
    Schema::dropIfExists('users');
    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('remember_token', 100)->nullable();
        $table->timestamps();
    });

    Schema::dropIfExists('password_reset_tokens');
    Schema::create('password_reset_tokens', function ($table) {
        $table->string('email')->primary();
        $table->string('token');
        $table->timestamp('created_at')->nullable();
    });

    config()->set('auth.providers.users.model', User::class);
    config()->set('cache.default', 'array');
});

function registerAuthPanel(?callable $configure = null): Panel
{
    $panel = Panel::make('test-auth')->path('admin');
    if ($configure) {
        $configure($panel);
    }

    return app(PanelManager::class)->register($panel);
}

it('shows the login page when login is enabled', function () {
    registerAuthPanel(fn ($p) => $p->login());

    test()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])->get('/admin/login')->assertOk();
});

it('returns 404 for login when login is disabled', function () {
    registerAuthPanel(fn ($p) => $p->login(false)->authMiddleware([]));

    test()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])->get('/admin/login')->assertNotFound();
});

it('logs a user in with valid credentials', function () {
    registerAuthPanel(fn ($p) => $p->login());

    $user = User::create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => Hash::make('password123'),
    ]);

    test()->post('/admin/login', [
        'email' => 'ada@example.com',
        'password' => 'password123',
    ])->assertRedirect('/admin');

    expect(auth()->id())->toBe($user->id);
});

it('rejects invalid credentials with a validation error', function () {
    registerAuthPanel(fn ($p) => $p->login());

    User::create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => Hash::make('password123'),
    ]);

    test()->from('/admin/login')->post('/admin/login', [
        'email' => 'ada@example.com',
        'password' => 'wrong',
    ])->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('throttles login after 5 failed attempts', function () {
    registerAuthPanel(fn ($p) => $p->login());

    User::create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => Hash::make('password123'),
    ]);

    foreach (range(1, 5) as $_) {
        test()->from('/admin/login')->post('/admin/login', [
            'email' => 'ada@example.com',
            'password' => 'wrong',
        ]);
    }

    $response = test()->from('/admin/login')->post('/admin/login', [
        'email' => 'ada@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('seconds');
});

it('returns 404 for register when registration is disabled', function () {
    registerAuthPanel(fn ($p) => $p->authMiddleware([]));

    test()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])
        ->get('/admin/register')->assertNotFound();
});

it('registers a new user and logs them in', function () {
    registerAuthPanel(fn ($p) => $p->registration());

    test()->post('/admin/register', [
        'name' => 'Grace',
        'email' => 'grace@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/admin');

    expect(User::where('email', 'grace@example.com')->exists())->toBeTrue();
    expect(auth()->check())->toBeTrue();
});

it('returns 404 for forgot-password when password reset is disabled', function () {
    registerAuthPanel(fn ($p) => $p->authMiddleware([]));

    test()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])
        ->get('/admin/forgot-password')->assertNotFound();
});

it('sends a password reset link', function () {
    Notification::fake();
    registerAuthPanel(fn ($p) => $p->passwordReset());

    User::create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => Hash::make('password123'),
    ]);

    test()->from('/admin/forgot-password')
        ->post('/admin/forgot-password', ['email' => 'ada@example.com'])
        ->assertSessionHasNoErrors();
});

it('resets a password with a valid token', function () {
    registerAuthPanel(fn ($p) => $p->passwordReset());

    $user = User::create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $token = Password::broker()->createToken($user);

    test()->post('/admin/reset-password', [
        'token' => $token,
        'email' => 'ada@example.com',
        'password' => 'new-password-99',
        'password_confirmation' => 'new-password-99',
    ])->assertRedirect('/admin/login');

    expect(Hash::check('new-password-99', $user->fresh()->password))->toBeTrue();
});

it('rejects reset with an invalid token', function () {
    registerAuthPanel(fn ($p) => $p->passwordReset());

    User::create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => Hash::make('old-password'),
    ]);

    test()->from('/admin/reset-password/bad')->post('/admin/reset-password', [
        'token' => 'bad-token',
        'email' => 'ada@example.com',
        'password' => 'new-password-99',
        'password_confirmation' => 'new-password-99',
    ])->assertSessionHasErrors('email');
});

it('returns 404 for profile when profile is disabled', function () {
    registerAuthPanel(fn ($p) => $p->authMiddleware([]));

    test()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => 'rocket'])
        ->get('/admin/profile')->assertNotFound();
});

it('updates the profile name and email', function () {
    registerAuthPanel(fn ($p) => $p->profile()->authMiddleware([]));

    $user = User::create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => Hash::make('password123'),
    ]);

    test()->actingAs($user)->put('/admin/profile', [
        'name' => 'Ada Lovelace',
        'email' => 'ada.lovelace@example.com',
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Ada Lovelace');
    expect($user->email)->toBe('ada.lovelace@example.com');
});

it('requires current password to change password', function () {
    registerAuthPanel(fn ($p) => $p->profile()->authMiddleware([]));

    $user = User::create([
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    test()->actingAs($user)->from('/admin/profile')->put('/admin/profile', [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'current_password' => 'wrong-password',
        'password' => 'new-password-99',
        'password_confirmation' => 'new-password-99',
    ])->assertSessionHasErrors('current_password');
});
