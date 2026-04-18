<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Support\EnumSupport;

final class SelectFilter implements Filter
{
    /**
     * @param  array<string, string>  $options
     */
    public function __construct(
        private readonly string $name,
        private readonly string $attribute,
        private readonly string $label,
        private array $options = [],
    ) {}

    /**
     * @param  class-string  $enumClass
     */
    public function enum(string $enumClass): self
    {
        $this->options = EnumSupport::toOptions($enumClass);

        return $this;
    }

    public function apply(Builder $query, Request $request): void
    {
        $value = $request->query($this->queryKey());
        if ($value === null || $value === '') {
            return;
        }

        $query->where($this->attribute, $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(Request $request): array
    {
        return [
            'type' => 'select',
            'name' => $this->name,
            'query_key' => $this->queryKey(),
            'label' => $this->label,
            'options' => $this->options,
            'value' => $request->query($this->queryKey()),
        ];
    }

    private function queryKey(): string
    {
        return 'filter_'.$this->name;
    }
}
