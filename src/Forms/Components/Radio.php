<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

use MaherElGamil\Rocket\Support\EnumSupport;

final class Radio extends Field
{
    /** @var array<string, string> */
    private array $options = [];

    private bool $inline = false;

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

    public function inline(bool $inline = true): self
    {
        $this->inline = $inline;

        return $this;
    }

    public function type(): string
    {
        return 'radio';
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
            'inline' => $this->inline,
        ];
    }
}
