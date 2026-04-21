<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use MaherElGamil\Rocket\Models\Export;

abstract class Exporter
{
    /** @var class-string<Model> */
    protected static string $model;

    public function __construct(protected readonly Export $export) {}

    /**
     * @return class-string<Model>
     */
    public static function getModel(): string
    {
        return static::$model;
    }

    /**
     * @return array<int, ExportColumn>
     */
    abstract public static function getColumns(): array;

    public function getExport(): Export
    {
        return $this->export;
    }

    /**
     * Build the base query for this export. Override to scope.
     */
    public static function query(): Builder
    {
        return static::$model::query();
    }

    public static function getJobConnection(): ?string
    {
        return null;
    }

    public static function getJobQueue(): ?string
    {
        return null;
    }

    public static function getJobBatchName(Export $export): string
    {
        return class_basename(static::class).' #'.$export->getKey();
    }

    public static function getChunkSize(): int
    {
        return (int) config('rocket.exports.chunk_size', 100);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return trans_choice(
            '{0} Your :resource export is empty.|{1} Your :resource export with :count row is ready.|[2,*] Your :resource export with :count rows is ready.',
            $export->successful_rows,
            [
                'resource' => Str::of(class_basename(static::getModel()))->snake(' ')->plural()->title(),
                'count' => number_format((int) $export->successful_rows),
            ]
        );
    }

    /**
     * @return array<int, string>
     */
    public static function getHeader(): array
    {
        return array_map(static fn (ExportColumn $c) => $c->getLabel(), static::getColumns());
    }

    /**
     * Serialize a model into one CSV row.
     *
     * @return array<int, string>
     */
    public function toRow(Model $record): array
    {
        return array_map(static fn (ExportColumn $c) => $c->getStateAsString($record), static::getColumns());
    }

    /**
     * Apply `withCount` for any count columns and eager loads.
     */
    public static function modifyQuery(Builder $query): Builder
    {
        $counts = [];
        foreach (static::getColumns() as $column) {
            if ($column->getCountsRelationship() !== null) {
                $counts[] = $column->getCountsRelationship();
            }
        }

        if ($counts !== []) {
            $query->withCount($counts);
        }

        return $query;
    }
}
