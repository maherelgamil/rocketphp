<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Columns;

final class BooleanColumn extends Column
{
    private string $trueIcon = 'check';

    private string $falseIcon = 'x';

    private string $trueColor = 'green';

    private string $falseColor = 'slate';

    public function trueIcon(string $icon): self
    {
        $this->trueIcon = $icon;

        return $this;
    }

    public function falseIcon(string $icon): self
    {
        $this->falseIcon = $icon;

        return $this;
    }

    public function trueColor(string $color): self
    {
        $this->trueColor = $color;

        return $this;
    }

    public function falseColor(string $color): self
    {
        $this->falseColor = $color;

        return $this;
    }

    public function type(): string
    {
        return 'boolean';
    }

    protected function extraProps(): array
    {
        return [
            'true_icon' => $this->trueIcon,
            'false_icon' => $this->falseIcon,
            'true_color' => $this->trueColor,
            'false_color' => $this->falseColor,
        ];
    }
}
