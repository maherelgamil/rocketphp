<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * For models using {@see SoftDeletes}: without (default), with, only.
 */
final class TrashedFilter implements Filter
{
    public function __construct(
        private readonly string $name = 'trashed',
    ) {}

    public function apply(Builder $query, Request $request): void
    {
        $model = $query->getModel();
        if (! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            return;
        }

        $value = (string) $request->query($this->queryKey(), 'without');

        match ($value) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array
    {
        return [
            'type' => 'trashed',
            'name' => $this->name,
            'query_key' => $this->queryKey(),
            'label' => 'Trashed',
            'options' => [
                'without' => 'Without trashed',
                'with' => 'With trashed',
                'only' => 'Only trashed',
            ],
            'value' => $request->query($this->queryKey(), 'without'),
        ];
    }

    private function queryKey(): string
    {
        return 'filter_'.$this->name;
    }
}
