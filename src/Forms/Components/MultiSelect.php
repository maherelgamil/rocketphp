<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

use MaherElGamil\Rocket\Support\EnumSupport;

final class MultiSelect extends Field
{
    /** @var array<string, string> */
    private array $options = [];

    /**
     * @param  array<string, string>  $options
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  class-string  $enumClass
     */
    public function enum(string $enumClass): self
    {
        $this->options = EnumSupport::toOptions($enumClass);

        return $this;
    }

    public function type(): string
    {
        return 'multi_select';
    }

    protected function typeRules(): array
    {
        return ['array'];
    }

    protected function extraProps(): array
    {
        return [
            'options' => $this->options,
        ];
    }
}
