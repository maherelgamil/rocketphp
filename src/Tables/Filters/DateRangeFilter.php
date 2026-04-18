<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class DateRangeFilter implements Filter
{
    public function __construct(
        private readonly string $name,
        private readonly string $attribute,
        private readonly string $label,
    ) {}

    public function apply(Builder $query, Request $request): void
    {
        $from = $request->query($this->fromKey());
        $until = $request->query($this->untilKey());

        if (is_string($from) && $from !== '') {
            $query->whereDate($this->attribute, '>=', $from);
        }

        if (is_string($until) && $until !== '') {
            $query->whereDate($this->attribute, '<=', $until);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array
    {
        return [
            'type' => 'date_range',
            'name' => $this->name,
            'label' => $this->label,
            'from_key' => $this->fromKey(),
            'until_key' => $this->untilKey(),
            'from' => $request->query($this->fromKey()),
            'until' => $request->query($this->untilKey()),
        ];
    }

    private function fromKey(): string
    {
        return 'filter_'.$this->name.'_from';
    }

    private function untilKey(): string
    {
        return 'filter_'.$this->name.'_until';
    }
}
