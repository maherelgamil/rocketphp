<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use MaherElGamil\Rocket\Resources\Resource;

final class BulkDeleteAction extends BulkAction
{
    public function __construct()
    {
        parent::__construct(
            name: 'bulk-delete',
            label: 'Delete selected',
            requiresConfirmation: true,
            destructive: true,
            icon: 'trash-2',
        );
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  class-string<resource>  $resourceClass
     */
    public function authorizeRecord(Request $request, string $resourceClass, Model $model): void
    {
        $resourceClass::authorizeForRequest($request, 'delete', $model);
    }

    /**
     * @param  Collection<int, Model>  $models
     */
    public function handle(Collection $models): void
    {
        $models->each->delete();
    }
}
