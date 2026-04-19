<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Resources\Resource;

final class ViewAction extends Action
{
    public function __construct()
    {
        parent::__construct(
            name: 'view',
            label: 'View',
            requiresConfirmation: false,
            destructive: false,
            icon: 'eye',
            link: true,
            routeSuffix: 'view',
            ability: 'view',
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
        $resourceClass::authorizeForRequest($request, 'view', $model);
    }

    public function handle(Model $model): void
    {
        // Link action — never executed server-side.
    }
}
