<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Resources\Resource;

abstract class Action
{
    protected function __construct(
        protected readonly string $name,
        protected readonly string $label,
        protected readonly bool $requiresConfirmation = true,
        protected readonly bool $destructive = false,
        protected readonly ?string $icon = null,
        protected readonly bool $link = false,
        protected readonly ?string $routeSuffix = null,
        protected readonly ?string $ability = null,
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
            'scope' => 'row',
            'link' => $this->link,
            'route_suffix' => $this->routeSuffix,
            'ability' => $this->ability,
        ];
    }

    /**
     * @param  class-string<resource>  $resourceClass
     */
    abstract public function authorize(Request $request, string $resourceClass, Model $model): void;

    abstract public function handle(Model $model): void;
}
