<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Exports\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use MaherElGamil\Rocket\Exports\Exporter;
use MaherElGamil\Rocket\Models\Export;

/**
 * Concatenates chunk files into the final CSV and marks the export completed.
 */
final class CompleteExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  class-string<Exporter>  $exporter
     */
    public function __construct(
        public readonly int $exportId,
        public readonly string $exporter,
    ) {}

    public function handle(): void
    {
        /** @var Export $export */
        $export = Export::query()->findOrFail($this->exportId);
        $disk = Storage::disk($export->file_disk);

        $dir = 'rocket-exports/'.$export->getKey();
        $files = collect($disk->files($dir))
            ->filter(fn (string $f) => str_ends_with($f, '.csv'))
            ->sort()
            ->values();

        $finalPath = 'rocket-exports/'.$export->getKey().'/'.$export->file_name;

        // Stream chunks into the final file via a local temp resource.
        $outPath = tempnam(sys_get_temp_dir(), 'rocket_export_');
        $out = fopen($outPath, 'w');

        foreach ($files as $file) {
            $contents = $disk->get($file);
            if ($contents !== null) {
                fwrite($out, $contents);
            }
        }

        fclose($out);

        $in = fopen($outPath, 'r');
        $disk->put($finalPath, $in);
        if (is_resource($in)) {
            fclose($in);
        }
        @unlink($outPath);

        // Clean up chunk files
        foreach ($files as $file) {
            if ($file !== $finalPath) {
                $disk->delete($file);
            }
        }

        $export->completed_at = now();
        $export->save();

        $this->notifyUser($export);
    }

    private function notifyUser(Export $export): void
    {
        if ($export->user_id === null) {
            return;
        }

        $userModel = config('auth.providers.users.model');
        if (! is_string($userModel) || ! class_exists($userModel)) {
            return;
        }

        $user = $userModel::query()->find($export->user_id);
        if ($user === null || ! method_exists($user, 'notify')) {
            return;
        }

        $user->notify(new \MaherElGamil\Rocket\Exports\Notifications\ExportReadyNotification(
            $export->getKey(),
            $this->exporter,
        ));
    }
}
