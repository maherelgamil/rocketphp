<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Panel;

use Illuminate\Support\Str;
use MaherElGamil\Rocket\Pages\DashboardPage;
use MaherElGamil\Rocket\Pages\Page;
use MaherElGamil\Rocket\Resources\Resource;
use MaherElGamil\Rocket\Support\Color;
use MaherElGamil\Rocket\Support\Density;
use MaherElGamil\Rocket\Support\Font;
use Symfony\Component\Finder\Finder;

final class Panel
{
    /** @var array<int, class-string<resource>> */
    private array $resources = [];

    /** @var array<int, object> */
    private array $widgets = [];

    /** @var array<int, class-string<Page>> */
    private array $pages = [];

    private string $path = 'admin';

    private string $brand;

    private ?string $domain;

    /** @var array<int, string> */
    private array $middleware;

    /** @var array<int, string> */
    private array $authMiddleware;

    private string $guard = 'web';

    private bool $loginEnabled = true;

    private bool $registrationEnabled = false;

    private bool $passwordResetEnabled = false;

    private bool $emailVerificationEnabled = false;

    private bool $profileEnabled = false;

    /** @var class-string|null */
    private ?string $loginPage = null;

    /** @var class-string|null */
    private ?string $registrationPage = null;

    /** @var class-string|null */
    private ?string $passwordResetPage = null;

    private bool $globalSearchEnabled = true;

    private string $globalSearchPlaceholder = 'Search...';

    private bool $notificationsEnabled = false;

    private int $dashboardColumns = 3;

    private ?bool $sidebarCollapsed = null;

    private bool $sidebarCollapsible = true;

    /** @var array<string, string> */
    private array $theme = [];

    private ?string $locale = null;

    /** @var array<int, string> */
    private array $availableLocales = [];

    public function __construct(private readonly string $id)
    {
        $this->brand = (string) config('rocket.brand.name', 'Rocket');
        $this->domain = config('rocket.routes.domain');
        $this->middleware = (array) config('rocket.routes.middleware', ['web']);
        $this->authMiddleware = (array) config('rocket.routes.auth_middleware', ['auth']);
    }

    public static function make(string $id): self
    {
        return new self($id);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function path(string $path): self
    {
        $this->path = trim($path, '/');

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function brand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function domain(?string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * @param  array<int, string>  $middleware
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * @param  array<int, string>  $middleware
     */
    public function authMiddleware(array $middleware): self
    {
        $this->authMiddleware = $middleware;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getAuthMiddleware(): array
    {
        return $this->authMiddleware;
    }

    public function guard(string $guard): self
    {
        $this->guard = $guard;

        return $this;
    }

    public function authGuard(string $guard): self
    {
        return $this->guard($guard);
    }

    public function getGuard(): string
    {
        return $this->guard;
    }

    public function login(bool $enabled = true): self
    {
        $this->loginEnabled = $enabled;

        return $this;
    }

    public function isLoginEnabled(): bool
    {
        return $this->loginEnabled;
    }

    public function registration(bool $enabled = true): self
    {
        $this->registrationEnabled = $enabled;

        return $this;
    }

    public function isRegistrationEnabled(): bool
    {
        return $this->registrationEnabled;
    }

    public function passwordReset(bool $enabled = true): self
    {
        $this->passwordResetEnabled = $enabled;

        return $this;
    }

    public function isPasswordResetEnabled(): bool
    {
        return $this->passwordResetEnabled;
    }

    public function emailVerification(bool $enabled = true): self
    {
        $this->emailVerificationEnabled = $enabled;

        return $this;
    }

    public function isEmailVerificationEnabled(): bool
    {
        return $this->emailVerificationEnabled;
    }

    public function profile(bool $enabled = true): self
    {
        $this->profileEnabled = $enabled;

        return $this;
    }

    public function isProfileEnabled(): bool
    {
        return $this->profileEnabled;
    }

    /**
     * @param  class-string  $page
     */
    public function loginPage(string $page): self
    {
        $this->loginPage = $page;

        return $this;
    }

    /**
     * @return class-string|null
     */
    public function getLoginPage(): ?string
    {
        return $this->loginPage;
    }

    /**
     * @param  class-string  $page
     */
    public function registrationPage(string $page): self
    {
        $this->registrationPage = $page;

        return $this;
    }

    /**
     * @return class-string|null
     */
    public function getRegistrationPage(): ?string
    {
        return $this->registrationPage;
    }

    /**
     * @param  class-string  $page
     */
    public function passwordResetPage(string $page): self
    {
        $this->passwordResetPage = $page;

        return $this;
    }

    /**
     * @return class-string|null
     */
    public function getPasswordResetPage(): ?string
    {
        return $this->passwordResetPage;
    }

    /**
     * @param  array<int, class-string<resource>>  $resources
     */
    public function resources(array $resources): self
    {
        $this->resources = array_values(array_unique(array_merge($this->resources, $resources)));

        return $this;
    }

    /**
     * @return array<int, class-string<resource>>
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @param  array<int, class-string<Page>>  $pageClasses
     */
    public function pages(array $pageClasses): static
    {
        $this->pages = $pageClasses;

        return $this;
    }

    /**
     * @return array<int, class-string<Page>>
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * Auto-discover Page classes in a directory.
     */
    public function discoverPages(string $directory, string $namespace): static
    {
        if (! is_dir($directory)) {
            return $this;
        }

        $realDirectory = realpath($directory) ?: $directory;
        $files = Finder::create()->in($directory)->files()->name('*.php');

        foreach ($files as $file) {
            $relativePath = str_replace($realDirectory.DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $class = $namespace.'\\'.str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relativePath
            );

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Page::class)) {
                continue;
            }

            if (! in_array($class, $this->pages, true)) {
                $this->pages[] = $class;
            }
        }

        return $this;
    }

    /**
     * Register Inertia dashboard widgets (objects exposing toArray(): array).
     *
     * @param  array<int, object>  $widgets
     */
    public function widgets(array $widgets): self
    {
        $this->widgets = array_values($widgets);

        return $this;
    }

    /**
     * @return array<int, object>
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    /**
     * Discover resource classes in a directory using convention-based scanning.
     *
     * Mirrors Filament's `discoverResources(in: ..., for: ...)` API.
     */
    public function discoverResources(string $in, string $for): self
    {
        if (! is_dir($in)) {
            return $this;
        }

        $discovered = [];

        foreach (Finder::create()->files()->in($in)->name('*.php') as $file) {
            $relative = str_replace(
                ['/', '.php'],
                ['\\', ''],
                ltrim(substr($file->getRealPath(), strlen($in)), DIRECTORY_SEPARATOR)
            );

            $class = rtrim($for, '\\').'\\'.ltrim($relative, '\\');

            if (! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, Resource::class)) {
                continue;
            }

            $discovered[] = $class;
        }

        return $this->resources($discovered);
    }

    public function default(): self
    {
        config(['rocket.default_panel' => $this->id]);

        return $this;
    }

    /**
     * @return class-string<resource>|null
     */
    public function findResourceBySlug(string $slug): ?string
    {
        foreach ($this->resources as $resource) {
            if ($resource::getSlug() === $slug) {
                return $resource;
            }
        }

        return null;
    }

    public function routeName(string $suffix = ''): string
    {
        return trim("rocket.{$this->id}.{$suffix}", '.');
    }

    public function url(string $path = ''): string
    {
        return '/'.trim($this->path.'/'.ltrim($path, '/'), '/');
    }

    public function globalSearchEnabled(bool $enabled = true): self
    {
        $this->globalSearchEnabled = $enabled;

        return $this;
    }

    public function isGlobalSearchEnabled(): bool
    {
        return $this->globalSearchEnabled;
    }

    public function globalSearchPlaceholder(string $placeholder): self
    {
        $this->globalSearchPlaceholder = $placeholder;

        return $this;
    }

    public function getGlobalSearchPlaceholder(): string
    {
        return $this->globalSearchPlaceholder;
    }

    public function notificationsEnabled(bool $enabled = true): self
    {
        $this->notificationsEnabled = $enabled;

        return $this;
    }

    public function isNotificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    public function dashboardColumns(int $columns): self
    {
        $this->dashboardColumns = max(1, $columns);

        return $this;
    }

    public function setColor(string $name, string|Color $value): self
    {
        $this->theme[$name] = $value instanceof Color ? $value->hsl() : $value;

        return $this;
    }

    public function setPrimaryColor(string|Color $color): self
    {
        return $this->setColor('primary', $color);
    }

    public function setAccentColor(string|Color $color): self
    {
        return $this->setColor('accent', $color);
    }

    public function setFont(string|Font $family): self
    {
        $this->theme['font'] = $family instanceof Font ? $family->value : $family;

        return $this;
    }

    public function setRadius(string $radius): self
    {
        $this->theme['radius'] = $radius;

        return $this;
    }

    public function setDensity(string|Density $density): self
    {
        $this->theme['density'] = $density instanceof Density ? $density->value : $density;

        return $this;
    }

    public function sidebarCollapsed(bool $collapsed = true): self
    {
        $this->sidebarCollapsed = $collapsed;

        return $this;
    }

    public function isSidebarCollapsed(): ?bool
    {
        return $this->sidebarCollapsed;
    }

    public function sidebarCollapsible(bool $collapsible = true): self
    {
        $this->sidebarCollapsible = $collapsible;

        return $this;
    }

    public function isSidebarCollapsible(): bool
    {
        return $this->sidebarCollapsible;
    }

    /**
     * @return array<string, string>
     */
    public function getTheme(): array
    {
        return $this->theme;
    }

    public function locale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale ?? app()->getLocale();
    }

    public function availableLocales(array $locales): self
    {
        $this->availableLocales = $locales;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getAvailableLocales(): array
    {
        return $this->availableLocales;
    }

    /**
     * @return array<string, string>
     */
    private function loadTranslations(): array
    {
        $locale = $this->getLocale();

        // 1. Check published host-app overrides first
        $publishedPath = lang_path('vendor/rocket/'.$locale.'.json');
        if (file_exists($publishedPath)) {
            return json_decode(file_get_contents($publishedPath), true) ?? [];
        }

        // 2. Fall back to package-bundled file
        $packagePath = __DIR__.'/../../lang/'.$locale.'.json';
        if (file_exists($packagePath)) {
            return json_decode(file_get_contents($packagePath), true) ?? [];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSharedProps(): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'path' => $this->path,
            'navigation' => $this->buildNavigation(),
            'global_search' => [
                'enabled' => $this->globalSearchEnabled,
                'placeholder' => $this->globalSearchPlaceholder,
                'url' => $this->url('search'),
            ],
            'theme' => $this->theme,
            'dashboard_columns' => $this->dashboardColumns,
            'sidebar_collapsed' => $this->sidebarCollapsed,
            'sidebar_collapsible' => $this->sidebarCollapsible,
            'notifications' => [
                'enabled' => $this->notificationsEnabled,
                'urls' => $this->notificationsEnabled ? [
                    'index' => $this->url('notifications'),
                    'recent' => $this->url('notifications/recent'),
                    'mark_all_read' => $this->url('notifications/read-all'),
                ] : [],
            ],
            'auth' => [
                'login' => $this->loginEnabled,
                'registration' => $this->registrationEnabled,
                'password_reset' => $this->passwordResetEnabled,
                'email_verification' => $this->emailVerificationEnabled,
                'profile' => $this->profileEnabled,
                'user' => $this->authUser(),
                'urls' => [
                    'login' => $this->loginEnabled ? $this->url('login') : null,
                    'logout' => $this->loginEnabled ? $this->url('logout') : null,
                    'register' => $this->registrationEnabled ? $this->url('register') : null,
                    'forgot_password' => $this->passwordResetEnabled ? $this->url('forgot-password') : null,
                    'profile' => $this->profileEnabled ? $this->url('profile') : null,
                ],
            ],
            'locale' => $this->getLocale(),
            'available_locales' => $this->getAvailableLocales(),
            'translations' => $this->loadTranslations(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildNavigation(): array
    {
        $req = \request();
        $items = [];

        if ($this->widgets !== []) {
            $dashboard = new DashboardPage;
            $items[] = [
                'label' => $dashboard->getNavigationLabel(),
                'icon' => $dashboard->getNavigationIcon(),
                'group' => $dashboard->getNavigationGroup(),
                'slug' => '__dashboard__',
                'url' => url($this->getPath().'/dashboard'),
            ];
        }

        foreach ($this->resources as $resource) {
            if (! $resource::can($req, 'viewAny')) {
                continue;
            }

            $items[] = [
                'label' => $resource::getPluralLabel(),
                'icon' => $resource::getNavigationIcon(),
                'group' => $resource::getNavigationGroup(),
                'slug' => $resource::getSlug(),
                'url' => $this->url($resource::getSlug()),
            ];
        }

        foreach ($this->pages as $pageClass) {
            $page = new $pageClass;

            if (! $page->shouldRegisterNavigation()) {
                continue;
            }

            if (! $page->can($req)) {
                continue;
            }

            $items[] = [
                'label' => $page->getNavigationLabel(),
                'icon' => $page->getNavigationIcon(),
                'group' => $page->getNavigationGroup(),
                'sort' => $page->getNavigationSort(),
                'slug' => $page->getSlug(),
                'url' => url($this->getPath().'/pages/'.$page->getSlug()),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authUser(): ?array
    {
        $user = auth()->guard($this->guard)->user();

        if ($user === null) {
            return null;
        }

        return [
            'name' => (string) ($user->name ?? $user->getAuthIdentifier()),
            'email' => (string) ($user->email ?? ''),
        ];
    }

    public function slugify(string $value): string
    {
        return Str::slug($value);
    }
}
