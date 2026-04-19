<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Panel;

use Illuminate\Support\Str;
use MaherElGamil\Rocket\Resources\Resource;
use Symfony\Component\Finder\Finder;

final class Panel
{
    /** @var array<int, class-string<resource>> */
    private array $resources = [];

    /** @var array<int, object> */
    private array $widgets = [];

    private string $path = 'admin';

    private string $brand;

    private ?string $domain;

    /** @var array<int, string> */
    private array $middleware;

    /** @var array<int, string> */
    private array $authMiddleware;

    private string $guard = 'web';

    private bool $globalSearchEnabled = true;

    private string $globalSearchPlaceholder = 'Search...';

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

    public function getGuard(): string
    {
        return $this->guard;
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
            $items[] = [
                'label' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'group' => null,
                'slug' => '__dashboard__',
                'url' => $this->url('dashboard'),
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

        return $items;
    }

    public function slugify(string $value): string
    {
        return Str::slug($value);
    }
}
