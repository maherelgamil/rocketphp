<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Imports;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ImportColumn
{
    private ?string $label = null;

    private bool $requiredMapping = false;

    /** @var array<int, string> */
    private array $guesses = [];

    /** @var array<int, mixed> */
    private array $rules = [];

    private ?string $example = null;

    private ?string $relationship = null;

    private ?string $relationshipResolveKey = null;

    private ?Closure $castStateUsing = null;

    private ?Closure $fillRecordUsing = null;

    private bool $boolean = false;

    private bool $numeric = false;

    private bool $ignoreBlankState = false;

    private ?string $arraySeparator = null;

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

    public function requiredMapping(bool $required = true): self
    {
        $this->requiredMapping = $required;

        return $this;
    }

    /**
     * @param  array<int, string>  $guesses
     */
    public function guess(array $guesses): self
    {
        $this->guesses = array_map(static fn ($g) => strtolower((string) $g), $guesses);

        return $this;
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    public function rules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function example(string $value): self
    {
        $this->example = $value;

        return $this;
    }

    public function relationship(string $name, string $resolveKey = 'id'): self
    {
        $this->relationship = $name;
        $this->relationshipResolveKey = $resolveKey;

        return $this;
    }

    public function castStateUsing(Closure $callback): self
    {
        $this->castStateUsing = $callback;

        return $this;
    }

    public function fillRecordUsing(Closure $callback): self
    {
        $this->fillRecordUsing = $callback;

        return $this;
    }

    public function boolean(): self
    {
        $this->boolean = true;

        return $this;
    }

    public function numeric(): self
    {
        $this->numeric = true;

        return $this;
    }

    public function array(string $separator = ','): self
    {
        $this->arraySeparator = $separator;

        return $this;
    }

    public function ignoreBlankState(bool $value = true): self
    {
        $this->ignoreBlankState = $value;

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

    public function isRequiredMapping(): bool
    {
        return $this->requiredMapping;
    }

    /**
     * @return array<int, string>
     */
    public function getGuesses(): array
    {
        $base = $this->guesses !== [] ? $this->guesses : [strtolower($this->name)];

        // Always also try the label (lowercased).
        $base[] = strtolower($this->getLabel());

        return array_values(array_unique($base));
    }

    /**
     * @return array<int, mixed>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    public function getExample(): ?string
    {
        return $this->example;
    }

    public function castState(mixed $state): mixed
    {
        if ($state === null || $state === '') {
            return $this->ignoreBlankState ? null : $state;
        }

        if ($this->boolean) {
            return in_array(strtolower((string) $state), ['1', 'true', 'yes', 'on', 'y'], true);
        }

        if ($this->numeric) {
            if (is_numeric($state)) {
                return str_contains((string) $state, '.') ? (float) $state : (int) $state;
            }
        }

        if ($this->arraySeparator !== null) {
            return array_values(array_filter(array_map('trim', explode($this->arraySeparator, (string) $state)), fn ($v) => $v !== ''));
        }

        if ($this->castStateUsing !== null) {
            return ($this->castStateUsing)($state);
        }

        return $state;
    }

    /**
     * @return array{0: string, 1: mixed}|null   [foreign key name, foreign key value] on success, null if unresolved
     */
    public function resolveRelationship(Model $record, mixed $state): ?array
    {
        if ($this->relationship === null) {
            return null;
        }

        $relation = $record->{$this->relationship}();
        $related = $relation->getRelated();
        $resolved = $related::query()->where($this->relationshipResolveKey, $state)->first();

        if ($resolved === null) {
            return null;
        }

        /** @phpstan-ignore-next-line */
        $foreignKey = $relation->getForeignKeyName();

        return [$foreignKey, $resolved->getKey()];
    }

    public function hasRelationship(): bool
    {
        return $this->relationship !== null;
    }

    public function getRelationshipName(): ?string
    {
        return $this->relationship;
    }

    public function fillRecord(Model $record, mixed $state): void
    {
        if ($this->fillRecordUsing !== null) {
            ($this->fillRecordUsing)($record, $state);

            return;
        }

        if ($this->hasRelationship()) {
            $resolved = $this->resolveRelationship($record, $state);
            if ($resolved !== null) {
                [$foreignKey, $value] = $resolved;
                $record->setAttribute($foreignKey, $value);
            }

            return;
        }

        $record->setAttribute($this->name, $state);
    }
}
