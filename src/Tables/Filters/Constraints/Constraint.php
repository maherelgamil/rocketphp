<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters\Constraints;

use Illuminate\Database\Eloquent\Builder;

abstract class Constraint
{
    protected string $name;

    protected ?string $label = null;

    protected ?string $attribute = null;

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

    public function attribute(string $attribute): static
    {
        $this->attribute = $attribute;

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

    public function getAttribute(): string
    {
        return $this->attribute ?? $this->name;
    }

    /**
     * Operators this constraint exposes. Map of operator key → human label.
     *
     * @return array<string, string>
     */
    abstract public function operators(): array;

    /**
     * Input variant the operator needs. One of: text, number, date, select, multi_select, none.
     */
    public function inputType(string $operator): string
    {
        return 'text';
    }

    /**
     * Apply this constraint to the query, on the given boolean (and|or).
     *
     * @param  mixed  $value
     */
    abstract public function apply(Builder $query, string $operator, $value, string $boolean = 'and'): void;

    /**
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        $operators = $this->operators();
        $inputs = [];
        foreach (array_keys($operators) as $op) {
            $inputs[$op] = $this->inputType($op);
        }

        return [
            'name' => $this->name,
            'label' => $this->getLabel(),
            'type' => static::class,
            'operators' => $operators,
            'input_types' => $inputs,
            'options' => $this->options(),
        ];
    }

    /**
     * @return array<string, string>|null
     */
    public function options(): ?array
    {
        return null;
    }
}
