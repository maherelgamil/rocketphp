<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use MaherElGamil\Rocket\Imports\Importer;
use MaherElGamil\Rocket\Imports\Jobs\PrepareImportJob;
use MaherElGamil\Rocket\Models\Import;
use MaherElGamil\Rocket\Resources\Resource;

/**
 * Header-level import action: accepts an uploaded CSV + column mapping,
 * persists an Import row, and dispatches PrepareImportJob.
 *
 * Upload flow:
 *   1. Client POSTs {file}.                      (no mapping yet)
 *   2. Client receives auto-guessed mapping.
 *   3. Client POSTs {file, mapping}.             (actual import)
 *
 * The controller handles step-1 ("preview") and step-2 via the
 * `action` query param: `preview` or `import` (default).
 */
final class ImportAction extends HeaderAction
{
    /**
     * @param  class-string<Importer>  $importer
     */
    public function __construct(
        private readonly string $importer,
        string $name = 'import',
        string $label = 'Import',
        ?string $icon = 'upload',
    ) {
        parent::__construct(
            name: $name,
            label: $label,
            requiresConfirmation: false,
            icon: $icon,
        );
    }

    /**
     * @param  class-string<Importer>  $importer
     */
    public static function make(string $importer): self
    {
        return new self($importer);
    }

    public function getImporter(): string
    {
        return $this->importer;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'importer_key' => base64_encode($this->importer),
        ]);
    }

    /**
     * @param  class-string<resource>  $resourceClass
     */
    public function authorize(Request $request, string $resourceClass): void
    {
        $resourceClass::authorizeForRequest($request, 'create');
    }

    public function handle(Request $request, Builder $query): void
    {
        unset($query);

        $request->validate([
            'file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel'],
            'mapping' => ['required', 'array'],
        ]);

        $disk = config('rocket.imports.disk') ?: config('filesystems.default');

        $import = Import::query()->create([
            'importer' => $this->importer,
            'file_name' => 'import.csv',
            'file_disk' => $disk,
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'user_id' => $request->user()?->getAuthIdentifier(),
        ]);

        $path = 'rocket-imports/'.$import->getKey().'/import.csv';
        Storage::disk($disk)->put($path, file_get_contents($request->file('file')->getRealPath()));

        /** @var array<string, mixed> $rawMapping */
        $rawMapping = (array) $request->input('mapping');
        $mapping = [];
        foreach ($rawMapping as $column => $index) {
            $mapping[(string) $column] = is_numeric($index) ? (int) $index : null;
        }

        PrepareImportJob::dispatch($import->getKey(), $this->importer, $mapping);
    }
}
