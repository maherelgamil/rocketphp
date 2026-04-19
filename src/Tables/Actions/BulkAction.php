<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use MaherElGamil\Rocket\Resources\Resource;

abstract class BulkAction
{
    protected function __construct(
        protected readonly string $name,
        protected readonly string $label,
        protected readonly bool $requiresConfirmation = true,
        protected readonly bool $destructive = false,
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
            'destructive' => $this->destructive,
            'icon' => $this->icon,
            'scope' => 'bulk',
        ];
    }

    /**
     * @param  class-string<resource>  $resourceClass
     */
    abstract public function authorizeRecord(Request $request, string $resourceClass, Model $model): void;

    /**
     * @param  Collection<int, Model>  $models
     */
    abstract public function handle(Collection $models): void;
}
