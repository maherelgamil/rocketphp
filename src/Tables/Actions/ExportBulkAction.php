<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use MaherElGamil\Rocket\Exports\Exporter;
use MaherElGamil\Rocket\Exports\Jobs\PrepareExportJob;
use MaherElGamil\Rocket\Models\Export;
use MaherElGamil\Rocket\Resources\Resource;

final class ExportBulkAction extends BulkAction
{
    /**
     * @param  class-string<Exporter>  $exporter
     */
    public function __construct(
        private readonly string $exporter,
        string $name = 'export',
        string $label = 'Export selected',
        ?string $icon = 'download',
    ) {
        parent::__construct(
            name: $name,
            label: $label,
            requiresConfirmation: false,
            destructive: false,
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

    /**
     * @param  class-string<Resource>  $resourceClass
     */
    public function authorizeRecord(Request $request, string $resourceClass, Model $model): void
    {
        $resourceClass::authorizeForRequest($request, 'viewAny');
    }

    /**
     * @param  Collection<int, Model>  $models
     */
    public function handle(Collection $models): void
    {
        $export = Export::query()->create([
            'exporter' => $this->exporter,
            'file_name' => $this->buildFileName(),
            'file_disk' => config('rocket.exports.disk') ?: config('filesystems.default'),
            'total_rows' => 0,
            'successful_rows' => 0,
            'user_id' => request()->user()?->getAuthIdentifier(),
        ]);

        PrepareExportJob::dispatch(
            $export->getKey(),
            $this->exporter,
            $models->map->getKey()->all(),
        );
    }

    public function getExporter(): string
    {
        return $this->exporter;
    }

    private function buildFileName(): string
    {
        $exporter = $this->exporter;
        $base = strtolower(class_basename($exporter::getModel()));

        return $base.'-'.now()->format('Ymd-His').'.csv';
    }
}
