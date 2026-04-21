<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Imports\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use MaherElGamil\Rocket\Imports\Importer;
use MaherElGamil\Rocket\Models\Import;

/**
 * Reads the CSV header, resolves the column mapping, partitions rows
 * into chunk jobs, and fans out as a Bus batch with a completion tail.
 */
final class PrepareImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  class-string<Importer>  $importer
     * @param  array<string, ?int>  $mapping   column name → CSV column index
     */
    public function __construct(
        public readonly int $importId,
        public readonly string $importer,
        public readonly array $mapping,
    ) {}

    public function handle(): void
    {
        /** @var Import $import */
        $import = Import::query()->findOrFail($this->importId);

        /** @var class-string<Importer> $importer */
        $importer = $this->importer;
        $disk = Storage::disk($import->file_disk);

        $path = 'rocket-imports/'.$import->getKey().'/'.$import->file_name;
        $contents = $disk->get($path);
        if ($contents === null) {
            $import->completed_at = now();
            $import->save();

            return;
        }

        $rows = $this->parseCsv($contents);

        if ($rows === []) {
            $import->completed_at = now();
            $import->save();

            return;
        }

        // Drop header
        $header = array_shift($rows);
        unset($header);

        $chunkSize = $importer::getChunkSize();
        $chunks = array_chunk($rows, $chunkSize);

        $import->total_rows = count($rows);
        $import->save();

        $jobs = [];
        foreach ($chunks as $chunkIndex => $chunkRows) {
            $jobs[] = new ImportCsvChunkJob(
                importId: $import->getKey(),
                importer: $importer,
                mapping: $this->mapping,
                rows: $chunkRows,
                chunkIndex: $chunkIndex,
            );
        }

        if ($jobs === []) {
            CompleteImportJob::dispatch($import->getKey(), $importer);

            return;
        }

        $pendingBatch = Bus::batch($jobs)
            ->name($importer::getJobBatchName($import))
            ->allowFailures()
            ->finally(function () use ($import, $importer): void {
                CompleteImportJob::dispatch($import->getKey(), $importer);
            });

        if ($importer::getJobConnection() !== null) {
            $pendingBatch->onConnection($importer::getJobConnection());
        }

        if ($importer::getJobQueue() !== null) {
            $pendingBatch->onQueue($importer::getJobQueue());
        }

        $batch = $pendingBatch->dispatch();

        $import->batch_id = $batch->id;
        $import->save();
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $contents): array
    {
        $rows = [];
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
