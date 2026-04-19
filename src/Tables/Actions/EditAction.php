<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Resources\Resource;

final class EditAction extends Action
{
    public function __construct()
    {
        parent::__construct(
            name: 'edit',
            label: 'Edit',
            requiresConfirmation: false,
            destructive: false,
            icon: 'pencil',
            link: true,
            routeSuffix: 'edit',
            ability: 'update',
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
        $resourceClass::authorizeForRequest($request, 'update', $model);
    }

    public function handle(Model $model): void
    {
        // Link action — never executed server-side.
    }
}
