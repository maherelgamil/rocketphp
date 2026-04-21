<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Imports\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use MaherElGamil\Rocket\Imports\Importer;
use MaherElGamil\Rocket\Models\FailedImportRow;
use MaherElGamil\Rocket\Models\Import;

/**
 * Processes one chunk of CSV rows: map → cast → validate → save.
 * Validation failures are persisted to rocket_failed_import_rows.
 */
final class ImportCsvChunkJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  class-string<Importer>  $importer
     * @param  array<string, ?int>  $mapping
     * @param  array<int, array<int, string>>  $rows
     */
    public function __construct(
        public readonly int $importId,
        public readonly string $importer,
        public readonly array $mapping,
        public readonly array $rows,
        public readonly int $chunkIndex,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        /** @var Import $import */
        $import = Import::query()->findOrFail($this->importId);

        /** @var class-string<Importer> $importer */
        $importer = $this->importer;

        $successful = 0;
        $failed = 0;
        $failedRows = [];

        foreach ($this->rows as $row) {
            $data = [];
            foreach ($this->mapping as $columnName => $csvIndex) {
                $data[$columnName] = $csvIndex !== null ? ($row[$csvIndex] ?? null) : null;
            }

            /** @var Importer $instance */
            $instance = new $importer($import);
            $instance->setRow($data);
            $cast = $instance->getData();

            $error = $importer::validate($cast);
            if ($error !== null) {
                $failed++;
                $failedRows[] = [
                    'import_id' => $import->getKey(),
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'validation_error' => $error,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                continue;
            }

            try {
                $ok = $instance->saveRow();
                if ($ok) {
                    $successful++;
                } else {
                    $failed++;
                    $failedRows[] = [
                        'import_id' => $import->getKey(),
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                        'validation_error' => 'Unable to save record.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            } catch (\Throwable $e) {
                $failed++;
                $failedRows[] = [
                    'import_id' => $import->getKey(),
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'validation_error' => $e->getMessage(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($failedRows !== []) {
            DB::table((new FailedImportRow)->getTable())->insert($failedRows);
        }

        $import->newQuery()
            ->whereKey($import->getKey())
            ->increment('processed_rows', $successful + $failed);
        $import->newQuery()
            ->whereKey($import->getKey())
            ->increment('successful_rows', $successful);
        if ($failed > 0) {
            $import->newQuery()
                ->whereKey($import->getKey())
                ->increment('failed_rows', $failed);
        }
    }
}
