<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Imports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use MaherElGamil\Rocket\Models\Import;

abstract class Importer
{
    /** @var class-string<Model> */
    protected static string $model;

    /** @var array<string, mixed>|null — current row, keyed by column name */
    protected ?array $data = null;

    /** @var array<string, mixed> — original CSV row by column name */
    protected array $originalData = [];

    public function __construct(protected readonly Import $import) {}

    /**
     * @return class-string<Model>
     */
    public static function getModel(): string
    {
        return static::$model;
    }

    /**
     * @return array<int, ImportColumn>
     */
    abstract public static function getColumns(): array;

    /**
     * Return the model to fill. Default: new instance. Override to find-or-new.
     */
    public function resolveRecord(): ?Model
    {
        return new (static::getModel());
    }

    public function getImport(): Import
    {
        return $this->import;
    }

    public static function getJobConnection(): ?string
    {
        return null;
    }

    public static function getJobQueue(): ?string
    {
        return null;
    }

    public static function getJobBatchName(Import $import): string
    {
        return class_basename(static::class).' #'.$import->getKey();
    }

    public static function getChunkSize(): int
    {
        return (int) config('rocket.imports.chunk_size', 100);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function setRow(array $row): self
    {
        $this->originalData = $row;
        $this->data = [];

        foreach (static::getColumns() as $column) {
            $value = $row[$column->getName()] ?? null;
            $this->data[$column->getName()] = $column->castState($value);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOriginalData(): array
    {
        return $this->originalData;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function getValidationRules(): array
    {
        $rules = [];
        foreach (static::getColumns() as $column) {
            if ($column->getRules() !== []) {
                $rules[$column->getName()] = $column->getRules();
            }
        }

        return $rules;
    }

    /**
     * Validate a cast row. Returns first error message, or null.
     *
     * @param  array<string, mixed>  $data
     */
    public static function validate(array $data): ?string
    {
        $rules = static::getValidationRules();
        if ($rules === []) {
            return null;
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return (string) $validator->errors()->first();
        }

        return null;
    }

    /**
     * Persist a single validated row. Returns true on success.
     */
    public function saveRow(): bool
    {
        $record = $this->resolveRecord();
        if ($record === null) {
            return false;
        }

        foreach (static::getColumns() as $column) {
            $state = $this->data[$column->getName()] ?? null;
            $column->fillRecord($record, $state);
        }

        return $record->save();
    }

    /**
     * Build a sample CSV from column labels + examples.
     */
    public static function getExampleCsv(): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, array_map(static fn (ImportColumn $c) => $c->getLabel(), static::getColumns()));
        fputcsv($handle, array_map(static fn (ImportColumn $c) => (string) ($c->getExample() ?? ''), static::getColumns()));

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    /**
     * Auto-guess a CSV-header-to-column mapping.
     *
     * @param  array<int, string>  $header
     * @return array<string, ?int>   column name → CSV index, or null when unmapped
     */
    public static function guessMapping(array $header): array
    {
        $normalized = array_map(static fn ($h) => strtolower(trim((string) $h)), $header);
        $map = [];

        foreach (static::getColumns() as $column) {
            $map[$column->getName()] = null;
            foreach ($column->getGuesses() as $guess) {
                $index = array_search($guess, $normalized, true);
                if ($index !== false) {
                    $map[$column->getName()] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $resource = Str::of(class_basename(static::getModel()))->snake(' ')->plural()->title();

        return "Your {$resource} import finished: ".number_format((int) $import->successful_rows)
            .' imported, '.number_format((int) $import->failed_rows).' failed.';
    }
}
