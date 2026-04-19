<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Resources\Resource;

final class DeleteAction extends Action
{
    public function __construct()
    {
        parent::__construct(
            name: 'delete',
            label: 'Delete',
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
    public function authorize(Request $request, string $resourceClass, Model $model): void
    {
        $resourceClass::authorizeForRequest($request, 'delete', $model);
    }

    public function handle(Model $model): void
    {
        $model->delete();
    }
}
