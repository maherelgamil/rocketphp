<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface Filter
{
    public function apply(Builder $query, Request $request): void;

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array;
}
