<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class Filter
{
    protected string $name;

    protected ?string $label = null;

    /** @var mixed */
    protected $default = null;

    protected ?Closure $indicator = null;

    protected bool $persistInSession = false;

    protected bool $visibleInDropdown = true;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): static
    {
        /** @phpstan-ignore-next-line */
        return new static($name);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param  mixed  $value
     */
    public function default($value): static
    {
        $this->default = $value;

        return $this;
    }

    public function indicator(string|Closure $indicator): static
    {
        $this->indicator = is_string($indicator)
            ? static fn () => $indicator
            : $indicator;

        return $this;
    }

    public function persistInSession(bool $persist = true): static
    {
        $this->persistInSession = $persist;

        return $this;
    }

    public function visibleInDropdown(bool $visible = true): static
    {
        $this->visibleInDropdown = $visible;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? ucfirst(str_replace(['_', '-'], ' ', $this->name));
    }

    public function isPersistent(): bool
    {
        return $this->persistInSession;
    }

    public function isVisibleInDropdown(): bool
    {
        return $this->visibleInDropdown;
    }

    abstract public function apply(Builder $query, Request $request): void;

    /**
     * @return array<string, mixed>
     */
    abstract public function toSchema(Request $request): array;

    /**
     * Read raw state for this filter from request, with legacy `filter_{name}` fallback,
     * with optional session persistence, and the configured default.
     *
     * @return mixed
     */
    protected function readState(Request $request, ?string $subKey = null, ?string $legacyKey = null)
    {
        $base = "filters.{$this->name}";
        $key = $subKey === null ? $base : "{$base}.{$subKey}";

        $value = $request->input($key);

        if ($value === null || $value === '') {
            $legacy = $legacyKey ?? ($subKey === null
                ? 'filter_'.$this->name
                : 'filter_'.$this->name.'_'.$subKey);
            $legacyValue = $request->query($legacy);
            if ($legacyValue !== null && $legacyValue !== '') {
                $value = $legacyValue;
            }
        }

        if (($value === null || $value === '') && $this->persistInSession) {
            $session = session(self::sessionKey($request, $this->name));
            if (is_array($session)) {
                $value = $subKey === null ? ($session['__value'] ?? null) : ($session[$subKey] ?? null);
            } elseif ($subKey === null) {
                $value = $session;
            }
        }

        if ($value === null || $value === '') {
            if ($subKey === null) {
                return $this->default;
            }
            if (is_array($this->default) && array_key_exists($subKey, $this->default)) {
                return $this->default[$subKey];
            }

            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function persistState(Request $request, array $state): void
    {
        if (! $this->persistInSession) {
            return;
        }

        $hasValue = false;
        foreach ($state as $v) {
            if ($v !== null && $v !== '') {
                $hasValue = true;
                break;
            }
        }

        $key = self::sessionKey($request, $this->name);
        if ($hasValue) {
            session([$key => $state]);
        } else {
            session()->forget($key);
        }
    }

    public static function sessionKey(Request $request, string $name): string
    {
        return 'rocket_filters.'.md5($request->path()).'.'.$name;
    }

    /**
     * Build indicator entries for active filter chips.
     *
     * @param  array<int, array{label: string, clear_keys: array<int, string>}>  $entries
     * @return array<int, array{label: string, clear_keys: array<int, string>}>
     */
    protected function buildIndicators(Request $request, array $entries): array
    {
        if ($this->indicator !== null) {
            $custom = ($this->indicator)($request, $this);
            if (is_string($custom) && $custom !== '') {
                $clear = [];
                foreach ($entries as $entry) {
                    foreach ($entry['clear_keys'] as $k) {
                        $clear[] = $k;
                    }
                }

                return [['label' => $custom, 'clear_keys' => array_values(array_unique($clear))]];
            }
        }

        return $entries;
    }
}
