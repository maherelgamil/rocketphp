<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Exports\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use MaherElGamil\Rocket\Exports\Exporter;
use MaherElGamil\Rocket\Models\Export;

/**
 * Splits the export query into chunk jobs, then chains a completion job.
 * Runs on the queue itself so counting and chunk planning don't block the request.
 */
final class PrepareExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  class-string<Exporter>  $exporter
     * @param  array<int, int|string>|null  $recordIds
     */
    public function __construct(
        public readonly int $exportId,
        public readonly string $exporter,
        public readonly ?array $recordIds = null,
    ) {}

    public function handle(): void
    {
        /** @var Export $export */
        $export = Export::query()->findOrFail($this->exportId);

        /** @var class-string<Exporter> $exporter */
        $exporter = $this->exporter;

        $query = $exporter::modifyQuery($exporter::query());

        if ($this->recordIds !== null) {
            $instance = $exporter::getModel()::query()->newModelInstance();
            $query->whereIn($instance->getKeyName(), $this->recordIds);
        }

        $total = (int) $query->toBase()->getCountForPagination();
        $export->total_rows = $total;
        $export->save();

        // Ensure base directory exists
        $disk = Storage::disk($export->file_disk);
        $disk->makeDirectory($this->chunksDirectory($export));

        // Write header file first
        $disk->put(
            $this->chunksDirectory($export).'/chunk_0000.csv',
            $this->rowToCsv($exporter::getHeader())
        );

        $chunkSize = $exporter::getChunkSize();
        $instance = $exporter::getModel()::query()->newModelInstance();
        $keyName = $instance->getKeyName();

        // Plan chunks using ID ranges to avoid loading everything in memory.
        $jobs = [];
        $query->clone()
            ->select($keyName)
            ->orderBy($keyName)
            ->chunk($chunkSize, function ($rows) use (&$jobs, $exporter, $export, $keyName, $chunkSize): void {
                static $index = 1;
                $ids = $rows->pluck($keyName)->all();
                $jobs[] = new ExportCsvChunkJob(
                    exportId: $export->getKey(),
                    exporter: $exporter,
                    recordIds: $ids,
                    chunkIndex: $index,
                );
                $index++;
                unset($chunkSize);
            });

        $batchName = $exporter::getJobBatchName($export);

        $pendingBatch = Bus::batch($jobs)
            ->name($batchName)
            ->allowFailures()
            ->finally(function () use ($export, $exporter): void {
                CompleteExportJob::dispatch($export->getKey(), $exporter);
            });

        if ($exporter::getJobConnection() !== null) {
            $pendingBatch->onConnection($exporter::getJobConnection());
        }

        if ($exporter::getJobQueue() !== null) {
            $pendingBatch->onQueue($exporter::getJobQueue());
        }

        $batch = $pendingBatch->dispatch();

        $export->batch_id = $batch->id;
        $export->save();

        // If the query was empty, no chunk jobs run — finalize immediately.
        if ($jobs === []) {
            CompleteExportJob::dispatch($export->getKey(), $exporter);
        }
    }

    private function chunksDirectory(Export $export): string
    {
        return 'rocket-exports/'.$export->getKey();
    }

    /**
     * @param  array<int, string>  $row
     */
    private function rowToCsv(array $row): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $row);
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }
}
