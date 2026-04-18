<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

final class TextInput extends Field
{
    private string $inputType = 'text';

    public function email(): self
    {
        $this->inputType = 'email';

        return $this;
    }

    public function password(): self
    {
        $this->inputType = 'password';

        return $this;
    }

    public function numeric(): self
    {
        $this->inputType = 'number';

        return $this;
    }

    public function url(): self
    {
        $this->inputType = 'url';

        return $this;
    }

    public function type(): string
    {
        return 'text';
    }

    protected function typeRules(): array
    {
        return match ($this->inputType) {
            'email' => ['email'],
            'url' => ['url'],
            'number' => ['numeric'],
            default => ['string'],
        };
    }

    protected function extraProps(): array
    {
        return ['input_type' => $this->inputType];
    }
}
