<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Tri-state filter for boolean columns: all (default), yes, no.
 */
final class TernaryFilter implements Filter
{
    public function __construct(
        private readonly string $name,
        private readonly string $attribute,
        private readonly string $label,
    ) {}

    public function apply(Builder $query, Request $request): void
    {
        $value = $request->query($this->queryKey());
        if ($value === null || $value === '' || $value === 'all') {
            return;
        }

        if ($value === '1' || $value === 'true' || $value === 'yes') {
            $query->where($this->attribute, true);

            return;
        }

        if ($value === '0' || $value === 'false' || $value === 'no') {
            $query->where($this->attribute, false);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array
    {
        return [
            'type' => 'ternary',
            'name' => $this->name,
            'query_key' => $this->queryKey(),
            'label' => $this->label,
            'value' => $request->query($this->queryKey(), 'all'),
        ];
    }

    private function queryKey(): string
    {
        return 'filter_'.$this->name;
    }
}
