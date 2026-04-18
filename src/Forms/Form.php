<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Forms\Components\Field;

final class Form
{
    /** @var array<int, Field> */
    private array $schema = [];

    private int $columns = 1;

    /**
     * @param  class-string<\MaherElGamil\Rocket\Resources\Resource>  $resource
     */
    public function __construct(private readonly string $resource) {}

    /**
     * @param  class-string<\MaherElGamil\Rocket\Resources\Resource>  $resource
     */
    public static function make(string $resource): self
    {
        return new self($resource);
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
    public function getSchema(): array
    {
        return $this->schema;
    }

    public function columns(int $columns): self
    {
        $this->columns = max(1, $columns);

        return $this;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getValidationRules(?Model $record = null): array
    {
        $rules = [];

        foreach ($this->schema as $field) {
            $rules[$field->getName()] = $field->getValidationRules($record);
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaults(): array
    {
        $defaults = [];

        foreach ($this->schema as $field) {
            $defaults[$field->getName()] = $field->getDefault();
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function processSubmission(Request $request, array $validated, ?Model $record = null): array
    {
        $data = $validated;

        foreach ($this->schema as $field) {
            $name = $field->getName();
            $result = $field->processSubmission($request, $data[$name] ?? null, $record);

            if ($result === Field::SKIP) {
                unset($data[$name]);

                continue;
            }

            $data[$name] = $result;
        }

        return $data;
    }

    public function extractState(Model $record): array
    {
        $state = [];

        foreach ($this->schema as $field) {
            $state[$field->getName()] = $field->getState($record);
        }

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(?Model $record = null): array
    {
        return [
            'columns' => $this->columns,
            'fields' => array_map(
                static fn (Field $field) => $field->toArray($record),
                $this->schema,
            ),
        ];
    }
}
