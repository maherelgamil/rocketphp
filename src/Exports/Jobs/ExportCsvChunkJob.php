<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Exports\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use MaherElGamil\Rocket\Exports\Exporter;
use MaherElGamil\Rocket\Models\Export;

/**
 * Streams one chunk of records to a CSV file on disk.
 * Uses fputcsv line-by-line so peak memory stays bounded by the chunk size.
 */
final class ExportCsvChunkJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  class-string<Exporter>  $exporter
     * @param  array<int, int|string>  $recordIds
     */
    public function __construct(
        public readonly int $exportId,
        public readonly string $exporter,
        public readonly array $recordIds,
        public readonly int $chunkIndex,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        /** @var Export $export */
        $export = Export::query()->findOrFail($this->exportId);

        /** @var class-string<Exporter> $exporter */
        $exporter = $this->exporter;
        $instance = $exporter::getModel()::query()->newModelInstance();
        $keyName = $instance->getKeyName();

        $query = $exporter::modifyQuery($exporter::query())
            ->whereIn($keyName, $this->recordIds)
            ->orderBy($keyName);

        $handle = fopen('php://temp', 'r+');
        $written = 0;
        $exporterInstance = new $exporter($export);

        foreach ($query->cursor() as $record) {
            fputcsv($handle, $exporterInstance->toRow($record));
            $written++;
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        $path = 'rocket-exports/'.$export->getKey().'/chunk_'.str_pad((string) $this->chunkIndex, 4, '0', STR_PAD_LEFT).'.csv';
        Storage::disk($export->file_disk)->put($path, $contents);

        // Atomically bump successful_rows
        $export->newQuery()
            ->whereKey($export->getKey())
            ->increment('successful_rows', $written);
    }
}
