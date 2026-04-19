<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Columns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use NumberFormatter;

final class TextColumn extends Column
{
    private bool $copyable = false;

    private ?string $prefix = null;

    private ?string $suffix = null;

    private ?int $limit = null;

    private string $limitEnd = '…';

    /** @var null|array{kind: 'money', currency: string, locale: ?string}|array{kind: 'number', decimals: int, locale: ?string}|array{kind: 'date', format: string}|array{kind: 'since'} */
    private ?array $formatter = null;

    public function copyable(bool $copyable = true): self
    {
        $this->copyable = $copyable;

        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(string $suffix): self
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function limit(int $length, string $end = '…'): self
    {
        $this->limit = $length;
        $this->limitEnd = $end;

        return $this;
    }

    public function money(string $currency = 'USD', ?string $locale = null): self
    {
        $this->formatter = ['kind' => 'money', 'currency' => $currency, 'locale' => $locale];

        return $this;
    }

    public function number(int $decimals = 0, ?string $locale = null): self
    {
        $this->formatter = ['kind' => 'number', 'decimals' => max(0, $decimals), 'locale' => $locale];

        return $this;
    }

    public function dateTime(string $format = 'Y-m-d H:i'): self
    {
        $this->formatter = ['kind' => 'date', 'format' => $format];

        return $this;
    }

    public function date(string $format = 'Y-m-d'): self
    {
        $this->formatter = ['kind' => 'date', 'format' => $format];

        return $this;
    }

    public function since(): self
    {
        $this->formatter = ['kind' => 'since'];

        return $this;
    }

    public function type(): string
    {
        return 'text';
    }

    public function render(Model $record): mixed
    {
        $state = parent::render($record);

        if ($state === null || $state === '') {
            return $state;
        }

        if ($this->formatter !== null) {
            $state = $this->applyFormatter($state);
        }

        $state = (string) $state;

        if ($this->limit !== null) {
            $state = Str::limit($state, $this->limit, $this->limitEnd);
        }

        if ($this->prefix !== null) {
            $state = $this->prefix.$state;
        }

        if ($this->suffix !== null) {
            $state = $state.$this->suffix;
        }

        return $state;
    }

    protected function extraProps(): array
    {
        return [
            'copyable' => $this->copyable,
        ];
    }

    private function applyFormatter(mixed $state): string
    {
        $kind = $this->formatter['kind'];

        if ($kind === 'money') {
            $locale = $this->formatter['locale'] ?? (function_exists('locale_get_default') ? locale_get_default() : 'en_US');
            $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);

            return (string) $fmt->formatCurrency((float) $state, $this->formatter['currency']);
        }

        if ($kind === 'number') {
            $locale = $this->formatter['locale'] ?? (function_exists('locale_get_default') ? locale_get_default() : 'en_US');
            $fmt = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            $fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $this->formatter['decimals']);
            $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $this->formatter['decimals']);

            return (string) $fmt->format((float) $state);
        }

        if ($kind === 'date') {
            $date = $this->toCarbon($state);

            return $date?->format($this->formatter['format']) ?? (string) $state;
        }

        if ($kind === 'since') {
            $date = $this->toCarbon($state);

            return $date?->diffForHumans() ?? (string) $state;
        }

        return (string) $state;
    }

    private function toCarbon(mixed $state): ?CarbonInterface
    {
        if ($state instanceof CarbonInterface) {
            return $state;
        }

        try {
            return Carbon::parse((string) $state);
        } catch (\Throwable) {
            return null;
        }
    }
}
