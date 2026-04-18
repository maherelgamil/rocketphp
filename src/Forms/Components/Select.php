<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

final class Select extends Field
{
    /** @var array<string, string> */
    private array $options = [];

    private bool $searchable = false;

    /**
     * @param  array<string, string>  $options
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function type(): string
    {
        return 'select';
    }

    protected function typeRules(): array
    {
        if ($this->options === []) {
            return [];
        }

        return ['in:'.implode(',', array_keys($this->options))];
    }

    protected function extraProps(): array
    {
        return [
            'options' => $this->options,
            'searchable' => $this->searchable,
        ];
    }
}
