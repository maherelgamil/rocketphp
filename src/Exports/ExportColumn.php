<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Exports;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ExportColumn
{
    private ?string $label = null;

    private ?Closure $formatStateUsing = null;

    private ?string $countsRelationship = null;

    private ?string $moneyCurrency = null;

    private int $moneyDivideBy = 1;

    private ?string $prefix = null;

    private ?string $suffix = null;

    private function __construct(private readonly string $name) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function formatStateUsing(Closure $callback): self
    {
        $this->formatStateUsing = $callback;

        return $this;
    }

    public function counts(string $relationship): self
    {
        $this->countsRelationship = $relationship;

        return $this;
    }

    public function money(string $currency = 'usd', int $divideBy = 100): self
    {
        $this->moneyCurrency = strtoupper($currency);
        $this->moneyDivideBy = max(1, $divideBy);

        return $this;
    }

    public function prefix(string $value): self
    {
        $this->prefix = $value;

        return $this;
    }

    public function suffix(string $value): self
    {
        $this->suffix = $value;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? (string) Str::of($this->name)->snake(' ')->title();
    }

    public function getCountsRelationship(): ?string
    {
        return $this->countsRelationship;
    }

    public function getState(Model $record): mixed
    {
        if ($this->countsRelationship !== null) {
            $attribute = Str::snake($this->countsRelationship).'_count';
            $state = $record->getAttribute($attribute);
        } else {
            $state = data_get($record, $this->name);
        }

        if ($this->formatStateUsing !== null) {
            $state = ($this->formatStateUsing)($state, $record);
        }

        if ($this->moneyCurrency !== null && is_numeric($state)) {
            $state = number_format(((float) $state) / $this->moneyDivideBy, 2, '.', '').' '.$this->moneyCurrency;
        }

        if ($this->prefix !== null && $state !== null) {
            $state = $this->prefix.$state;
        }

        if ($this->suffix !== null && $state !== null) {
            $state = $state.$this->suffix;
        }

        return $state;
    }

    public function getStateAsString(Model $record): string
    {
        $state = $this->getState($record);

        if ($state === null) {
            return '';
        }

        if (is_bool($state)) {
            return $state ? '1' : '0';
        }

        if ($state instanceof \BackedEnum) {
            return (string) $state->value;
        }

        if ($state instanceof \UnitEnum) {
            return $state->name;
        }

        if ($state instanceof \DateTimeInterface) {
            return $state->format('Y-m-d H:i:s');
        }

        if (is_array($state) || is_object($state)) {
            return (string) json_encode($state);
        }

        return (string) $state;
    }
}
