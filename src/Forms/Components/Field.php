<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Field
{
    public const SKIP = '__ROCKET_FIELD_SKIP__';

    protected ?string $label = null;

    protected ?string $placeholder = null;

    protected ?string $helperText = null;

    protected mixed $default = null;

    protected bool $disabled = false;

    /** @var bool|Closure */
    protected bool|Closure $required = false;

    /** @var bool|Closure */
    protected bool|Closure $nullable = false;

    protected ?int $max = null;

    protected ?int $min = null;

    /** @var array<int, string> */
    protected array $rules = [];

    /** @var array{table: string, column: ?string}|null */
    protected ?array $unique = null;

    /** @var class-string<\MaherElGamil\Rocket\Resources\Resource>|null */
    protected ?string $resourceClass = null;

    final public function __construct(protected readonly string $name) {}

    /**
     * @param  class-string<\MaherElGamil\Rocket\Resources\Resource>  $resource
     */
    public function setResource(string $resource): static
    {
        $this->resourceClass = $resource;

        return $this;
    }

    /**
     * @return class-string<\MaherElGamil\Rocket\Resources\Resource>|null
     */
    public function getResource(): ?string
    {
        return $this->resourceClass;
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? (string) Str::of($this->name)->replace('_', ' ')->title();
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function helperText(string $text): static
    {
        $this->helperText = $text;

        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function required(bool|Closure $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function nullable(bool|Closure $nullable = true): static
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function max(int $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function min(int $min): static
    {
        $this->min = $min;

        return $this;
    }

    /**
     * @param  array<int, string>  $rules
     */
    public function rules(array $rules): static
    {
        $this->rules = array_merge($this->rules, $rules);

        return $this;
    }

    public function unique(string $table, ?string $column = null): static
    {
        $this->unique = ['table' => $table, 'column' => $column];

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getValidationRules(?Model $record = null): array
    {
        $rules = [];

        if ($this->resolveBool($this->required, $record)) {
            $rules[] = 'required';
        } elseif ($this->resolveBool($this->nullable, $record)) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'sometimes';
        }

        if ($this->max !== null) {
            $rules[] = "max:{$this->max}";
        }

        if ($this->min !== null) {
            $rules[] = "min:{$this->min}";
        }

        if ($this->unique !== null) {
            $rule = 'unique:'.$this->unique['table'].','.($this->unique['column'] ?? $this->name);

            if ($record !== null) {
                $rule .= ','.$record->getKey().','.$record->getKeyName();
            }

            $rules[] = $rule;
        }

        return array_merge($rules, $this->rules, $this->typeRules());
    }

    public function isRequired(?Model $record = null): bool
    {
        return $this->resolveBool($this->required, $record);
    }

    public function getState(Model $record): mixed
    {
        return data_get($record, $this->name);
    }

    public function processSubmission(Request $request, mixed $value, ?Model $record): mixed
    {
        return $value;
    }

    public function afterSave(Model $record, mixed $value): void
    {
        // no-op by default
    }

    abstract public function type(): string;

    /**
     * @return array<int, string>
     */
    protected function typeRules(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraProps(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(?Model $record = null): array
    {
        return [
            'type' => $this->type(),
            'name' => $this->name,
            'label' => $this->getLabel(),
            'placeholder' => $this->placeholder,
            'helper_text' => $this->helperText,
            'default' => $this->default,
            'disabled' => $this->disabled,
            'required' => $this->isRequired($record),
            'extra' => $this->extraProps(),
        ];
    }

    private function resolveBool(bool|Closure $value, ?Model $record): bool
    {
        if ($value instanceof Closure) {
            return (bool) $value($record);
        }

        return $value;
    }
}
