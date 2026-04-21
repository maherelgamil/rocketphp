<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Exports\Exporter;
use MaherElGamil\Rocket\Exports\Jobs\PrepareExportJob;
use MaherElGamil\Rocket\Models\Export;
use MaherElGamil\Rocket\Resources\Resource;

/**
 * Header-level export action: exports the full filtered query
 * (as opposed to ExportBulkAction which exports selected rows).
 */
final class ExportAction extends HeaderAction
{
    /**
     * @param  class-string<Exporter>  $exporter
     */
    public function __construct(
        private readonly string $exporter,
        string $name = 'export',
        string $label = 'Export',
        ?string $icon = 'download',
    ) {
        parent::__construct(
            name: $name,
            label: $label,
            requiresConfirmation: false,
            icon: $icon,
        );
    }

    /**
     * @param  class-string<Exporter>  $exporter
     */
    public static function make(string $exporter): self
    {
        return new self($exporter);
    }

    public function getExporter(): string
    {
        return $this->exporter;
    }

    /**
     * @param  class-string<Resource>  $resourceClass
     */
    public function authorize(Request $request, string $resourceClass): void
    {
        $resourceClass::authorizeForRequest($request, 'viewAny');
    }

    public function handle(Request $request, Builder $query): void
    {
        $export = Export::query()->create([
            'exporter' => $this->exporter,
            'file_name' => $this->buildFileName(),
            'file_disk' => config('rocket.exports.disk') ?: config('filesystems.default'),
            'total_rows' => 0,
            'successful_rows' => 0,
            'user_id' => $request->user()?->getAuthIdentifier(),
        ]);

        // Resolve record ids up front so we don't serialize the query builder.
        $instance = $query->getModel();
        $keyName = $instance->getKeyName();
        $ids = $query->toBase()->pluck($keyName)->all();

        PrepareExportJob::dispatch($export->getKey(), $this->exporter, $ids);
    }

    private function buildFileName(): string
    {
        $exporter = $this->exporter;
        $base = strtolower(class_basename($exporter::getModel()));

        return $base.'-'.now()->format('Ymd-His').'.csv';
    }
}
