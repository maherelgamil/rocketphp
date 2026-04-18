<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

final class DatePicker extends Field
{
    private bool $withTime = false;

    public function withTime(bool $withTime = true): self
    {
        $this->withTime = $withTime;

        return $this;
    }

    public function type(): string
    {
        return 'date';
    }

    protected function typeRules(): array
    {
        return [$this->withTime ? 'date' : 'date_format:Y-m-d'];
    }

    protected function extraProps(): array
    {
        return ['with_time' => $this->withTime];
    }
}
