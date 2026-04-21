<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Imports\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use MaherElGamil\Rocket\Imports\Importer;
use MaherElGamil\Rocket\Models\Import;

final class ImportCompletedNotification extends Notification
{
    use Queueable;

    /**
     * @param  class-string<Importer>  $importer
     */
    public function __construct(
        public readonly int $importId,
        public readonly string $importer,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        /** @var Import|null $import */
        $import = Import::query()->find($this->importId);

        /** @var class-string<Importer> $importer */
        $importer = $this->importer;

        return [
            'type' => 'rocket.import.completed',
            'import_id' => $this->importId,
            'successful_rows' => $import?->successful_rows,
            'failed_rows' => $import?->failed_rows,
            'body' => $import !== null ? $importer::getCompletedNotificationBody($import) : 'Import completed.',
        ];
    }
}
