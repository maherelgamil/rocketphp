<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Resources\Resource;

abstract class HeaderAction
{
    protected function __construct(
        protected readonly string $name,
        protected readonly string $label,
        protected readonly bool $requiresConfirmation = false,
        protected readonly ?string $icon = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'requires_confirmation' => $this->requiresConfirmation,
            'icon' => $this->icon,
            'scope' => 'header',
        ];
    }

    /**
     * @param  class-string<Resource>  $resourceClass
     */
    abstract public function authorize(Request $request, string $resourceClass): void;

    /**
     * Called with the already-scoped query (filters and search applied).
     */
    abstract public function handle(Request $request, Builder $query): void;
}
