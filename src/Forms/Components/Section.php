<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

use Illuminate\Database\Eloquent\Model;

final class Section
{
    private ?string $description = null;

    private int $columns = 1;

    private bool $collapsible = false;

    private bool $collapsed = false;

    /** @var array<int, Field> */
    private array $schema = [];

    public function __construct(private readonly string $label) {}

    public static function make(string $label): self
    {
        return new self($label);
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function columns(int $columns): self
    {
        $this->columns = max(1, $columns);

        return $this;
    }

    public function collapsible(bool $collapsible = true): self
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    public function collapsed(bool $collapsed = true): self
    {
        $this->collapsed = $collapsed;

        if ($collapsed) {
            $this->collapsible = true;
        }

        return $this;
    }

    /**
     * @param  array<int, Field>  $schema
     */
    public function schema(array $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @return array<int, Field>
     */
    public function getFields(): array
    {
        return $this->schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(?Model $record = null): array
    {
        return [
            'type' => 'section',
            'label' => $this->label,
            'description' => $this->description,
            'columns' => $this->columns,
            'collapsible' => $this->collapsible,
            'collapsed' => $this->collapsed,
            'fields' => array_map(
                static fn (Field $field) => $field->toArray($record),
                $this->schema,
            ),
        ];
    }
}
