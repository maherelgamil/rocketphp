<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Panel;

use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use MaherElGamil\Rocket\Http\Controllers\DashboardController;
use MaherElGamil\Rocket\Http\Controllers\GlobalSearchController;
use MaherElGamil\Rocket\Http\Controllers\ImportController;
use MaherElGamil\Rocket\Http\Controllers\LocaleController;
use MaherElGamil\Rocket\Http\Controllers\NotificationController;
use MaherElGamil\Rocket\Http\Controllers\PageController;
use MaherElGamil\Rocket\Http\Controllers\PanelAuthController;
use MaherElGamil\Rocket\Http\Controllers\ResourceController;
use MaherElGamil\Rocket\Http\Middleware\Authenticate as RocketAuthenticate;
use MaherElGamil\Rocket\Http\Middleware\HandleRocketRequests;
use MaherElGamil\Rocket\Http\Middleware\RenderRocketErrorPages;

final class PanelManager
{
    /** @var array<string, Panel> */
    private array $panels = [];

    private ?string $current = null;

    public function panel(string $id): Panel
    {
        return $this->panels[$id] ??= Panel::make($id);
    }

    public function register(Panel $panel): Panel
    {
        $this->panels[$panel->id()] = $panel;
        $this->registerRoutes($panel);

        return $panel;
    }

    public function get(string $id): Panel
    {
        if (! isset($this->panels[$id])) {
            throw new InvalidArgumentException("Rocket panel [{$id}] is not registered.");
        }

        return $this->panels[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->panels[$id]);
    }

    public function setCurrent(string $id): void
    {
        $this->current = $id;
    }

    public function getCurrent(): Panel
    {
        $id = $this->current ?? config('rocket.default_panel', 'admin');

        return $this->get($id);
    }

    /**
     * @return array<string, Panel>
     */
    public function all(): array
    {
        return $this->panels;
    }

    private function registerRoutes(Panel $panel): void
    {
        $baseMiddleware = array_values(array_filter(array_merge(
            $panel->getMiddleware(),
            [HandleRocketRequests::class, RenderRocketErrorPages::class],
        )));

        $authedMiddleware = array_values(array_filter(array_merge(
            $panel->getMiddleware(),
            $this->resolveAuthMiddleware($panel),
            [HandleRocketRequests::class, RenderRocketErrorPages::class],
        )));

        $publicAttrs = [
            'prefix' => $panel->getPath(),
            'as' => "rocket.{$panel->id()}.",
            'middleware' => $baseMiddleware,
        ];

        $attributes = [
            'prefix' => $panel->getPath(),
            'as' => "rocket.{$panel->id()}.",
            'middleware' => $authedMiddleware,
        ];

        if ($panel->getDomain() !== null) {
            $publicAttrs['domain'] = $panel->getDomain();
            $attributes['domain'] = $panel->getDomain();
        }

        $this->registerAuthRoutes($panel, $publicAttrs);

        Route::group($attributes, function () use ($panel): void {
            $resourceConstraint = '[a-z0-9\-_]+';
            $recordConstraint = '[A-Za-z0-9\-_]+';
            $defaults = ['panelId' => $panel->id()];

            Route::get('dashboard', [DashboardController::class, 'show'])
                ->defaults('panelId', $panel->id())
                ->name('dashboard');

            Route::get('/', [DashboardController::class, 'show'])
                ->defaults('panelId', $panel->id())
                ->name('dashboard');

            if ($panel->isGlobalSearchEnabled()) {
                Route::get('search', [GlobalSearchController::class, 'search'])
                    ->defaults('panelId', $panel->id())
                    ->name('search');
            }

            if ($panel->isNotificationsEnabled()) {
                Route::get('notifications', [NotificationController::class, 'index'])
                    ->defaults('panelId', $panel->id())
                    ->name('notifications.index');

                Route::get('notifications/recent', [NotificationController::class, 'recent'])
                    ->defaults('panelId', $panel->id())
                    ->name('notifications.recent');

                Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
                    ->defaults('panelId', $panel->id())
                    ->name('notifications.read-all');

                Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
                    ->defaults('panelId', $panel->id())
                    ->name('notifications.read');
            }

            Route::post('locale', [LocaleController::class, 'store'])
                ->defaults('panelId', $panel->id())
                ->name('locale.store');

            Route::get('pages/{page}', [PageController::class, 'show'])
                ->defaults('panelId', $panel->id())
                ->name('pages.show')
                ->where('page', '[a-z0-9\-_]+');

            Route::post('pages/{page}/actions/{action}', [PageController::class, 'action'])
                ->defaults('panelId', $panel->id())
                ->name('pages.action')
                ->where('page', '[a-z0-9\-_]+')
                ->where('action', '[a-z0-9\-_]+');

            Route::post('{resource}/header-actions/{headerAction}', [ResourceController::class, 'headerAction'])
                ->defaults('panelId', $panel->id())
                ->name('resource.header-action')
                ->where('resource', $resourceConstraint)
                ->where('headerAction', '[a-z0-9\-_]+');

            Route::get('exports/{export}/download', [ResourceController::class, 'downloadExport'])
                ->defaults('panelId', $panel->id())
                ->name('exports.download')
                ->where('export', '[0-9]+');

            Route::get('imports/{import}', [ImportController::class, 'show'])
                ->defaults('panelId', $panel->id())
                ->name('imports.show')
                ->where('import', '[0-9]+');

            Route::get('imports/{import}/status', [ImportController::class, 'status'])
                ->defaults('panelId', $panel->id())
                ->name('imports.status')
                ->where('import', '[0-9]+');

            Route::get('imports/{import}/failed-rows.csv', [ImportController::class, 'downloadFailedRows'])
                ->defaults('panelId', $panel->id())
                ->name('imports.failed-rows')
                ->where('import', '[0-9]+');

            Route::get('importers/{importer}/example.csv', [ImportController::class, 'example'])
                ->defaults('panelId', $panel->id())
                ->name('importers.example')
                ->where('importer', '[A-Za-z0-9=+\/]+');

            Route::post('importers/{importer}/preview', [ImportController::class, 'preview'])
                ->defaults('panelId', $panel->id())
                ->name('importers.preview')
                ->where('importer', '[A-Za-z0-9=+\/]+');

            Route::post('{resource}/bulk-actions/{bulkAction}', [ResourceController::class, 'bulkAction'])
                ->defaults('panelId', $panel->id())
                ->name('resource.bulk-action')
                ->where('resource', $resourceConstraint)
                ->where('bulkAction', '[a-z0-9\-_]+');

            // Row action and custom-page action share the same shape
            // (/{resource}/{x}/actions/{action}); a single dispatcher
            // decides whether {x} is a record id or a custom page slug.
            Route::post('{resource}/{recordOrPage}/actions/{action}', [ResourceController::class, 'recordOrPageAction'])
                ->defaults('panelId', $panel->id())
                ->name('resource.row-action')
                ->where(['resource' => $resourceConstraint, 'recordOrPage' => $recordConstraint])
                ->where('action', '[a-z0-9\-_]+');

            Route::get('{resource}', [ResourceController::class, 'index'])
                ->defaults('panelId', $panel->id())
                ->name('resource.index')
                ->where('resource', $resourceConstraint);

            Route::get('{resource}/create', [ResourceController::class, 'create'])
                ->defaults('panelId', $panel->id())
                ->name('resource.create')
                ->where('resource', $resourceConstraint);

            Route::post('{resource}', [ResourceController::class, 'store'])
                ->defaults('panelId', $panel->id())
                ->name('resource.store')
                ->where('resource', $resourceConstraint);

            Route::get('{resource}/lookup/{field}', [ResourceController::class, 'lookup'])
                ->defaults('panelId', $panel->id())
                ->name('resource.lookup')
                ->where(['resource' => $resourceConstraint, 'field' => '[a-zA-Z0-9\._\-]+']);

            Route::get('{resource}/{record}/edit', [ResourceController::class, 'edit'])
                ->defaults('panelId', $panel->id())
                ->name('resource.edit')
                ->where(['resource' => $resourceConstraint, 'record' => $recordConstraint]);

            Route::get('{resource}/{record}/view', [ResourceController::class, 'view'])
                ->defaults('panelId', $panel->id())
                ->name('resource.view')
                ->where(['resource' => $resourceConstraint, 'record' => $recordConstraint]);

            Route::get('{resource}/{pageSlug}', [ResourceController::class, 'customPage'])
                ->defaults('panelId', $panel->id())
                ->name('resources.custom-page')
                ->where([
                    'resource' => $resourceConstraint,
                    'pageSlug' => '[a-z0-9\-_]+',
                ]);

            Route::match(['put', 'patch'], '{resource}/{record}', [ResourceController::class, 'update'])
                ->defaults('panelId', $panel->id())
                ->name('resource.update')
                ->where(['resource' => $resourceConstraint, 'record' => $recordConstraint]);

            Route::delete('{resource}/{record}', [ResourceController::class, 'destroy'])
                ->defaults('panelId', $panel->id())
                ->name('resource.destroy')
                ->where(['resource' => $resourceConstraint, 'record' => $recordConstraint]);

            unset($defaults);
        });
    }

    /**
     * Swap the framework's `auth` middleware for the panel-aware Authenticate
     * middleware so unauthenticated requests are redirected to the panel's
     * own login page instead of the host app's `login` named route.
     *
     * @return array<int, string>
     */
    private function resolveAuthMiddleware(Panel $panel): array
    {
        if (! $panel->isLoginEnabled()) {
            return $panel->getAuthMiddleware();
        }

        return array_map(static function (string $middleware) use ($panel): string {
            if ($middleware === 'auth') {
                return RocketAuthenticate::class.':'.$panel->getGuard();
            }

            if (str_starts_with($middleware, 'auth:')) {
                $guard = substr($middleware, 5);

                return RocketAuthenticate::class.($guard !== '' ? ':'.$guard : '');
            }

            return $middleware;
        }, $panel->getAuthMiddleware());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function registerAuthRoutes(Panel $panel, array $attributes): void
    {
        Route::group($attributes, function () use ($panel): void {
            if ($panel->isLoginEnabled()) {
                Route::get('login', [PanelAuthController::class, 'showLogin'])
                    ->defaults('panelId', $panel->id())
                    ->name('login');

                Route::post('login', [PanelAuthController::class, 'login'])
                    ->defaults('panelId', $panel->id())
                    ->name('login.attempt');

                Route::post('logout', [PanelAuthController::class, 'logout'])
                    ->defaults('panelId', $panel->id())
                    ->name('logout');
            }

            if ($panel->isRegistrationEnabled()) {
                Route::get('register', [PanelAuthController::class, 'showRegister'])
                    ->defaults('panelId', $panel->id())
                    ->name('register');

                Route::post('register', [PanelAuthController::class, 'register'])
                    ->defaults('panelId', $panel->id())
                    ->name('register.store');
            }

            if ($panel->isPasswordResetEnabled()) {
                Route::get('forgot-password', [PanelAuthController::class, 'showForgot'])
                    ->defaults('panelId', $panel->id())
                    ->name('password.request');

                Route::post('forgot-password', [PanelAuthController::class, 'sendResetLink'])
                    ->defaults('panelId', $panel->id())
                    ->name('password.email');

                Route::get('reset-password/{token}', [PanelAuthController::class, 'showReset'])
                    ->defaults('panelId', $panel->id())
                    ->name('password.reset');

                Route::post('reset-password', [PanelAuthController::class, 'resetPassword'])
                    ->defaults('panelId', $panel->id())
                    ->name('password.update');
            }

            if ($panel->isEmailVerificationEnabled()) {
                Route::get('verify-email', [PanelAuthController::class, 'showVerify'])
                    ->defaults('panelId', $panel->id())
                    ->name('verification.notice');

                Route::get('verify-email/{id}/{hash}', [PanelAuthController::class, 'verify'])
                    ->defaults('panelId', $panel->id())
                    ->middleware('signed')
                    ->name('verification.verify');

                Route::post('verify-email/resend', [PanelAuthController::class, 'resendVerification'])
                    ->defaults('panelId', $panel->id())
                    ->name('verification.send');
            }
        });

        if ($panel->isProfileEnabled()) {
            $authed = $attributes;
            $authed['middleware'] = array_values(array_filter(array_merge(
                $panel->getMiddleware(),
                $this->resolveAuthMiddleware($panel),
                [HandleRocketRequests::class, RenderRocketErrorPages::class],
            )));

            Route::group($authed, function () use ($panel): void {
                Route::get('profile', [PanelAuthController::class, 'showProfile'])
                    ->defaults('panelId', $panel->id())
                    ->name('profile.show');

                Route::match(['put', 'patch'], 'profile', [PanelAuthController::class, 'updateProfile'])
                    ->defaults('panelId', $panel->id())
                    ->name('profile.update');
            });
        }
    }
}
