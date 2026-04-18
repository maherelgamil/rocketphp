<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Columns;

use Illuminate\Database\Eloquent\Model;
use MaherElGamil\Rocket\Support\Color;
use MaherElGamil\Rocket\Support\EnumSupport;
use UnitEnum;

final class IconColumn extends Column
{
    private ?string $icon = null;

    /** @var array<string, string> */
    private array $icons = [];

    private ?string $color = null;

    /** @var array<string, string> */
    private array $colors = [];

    private int $size = 20;

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param  array<string, string>  $icons
     */
    public function icons(array $icons): self
    {
        $this->icons = $icons;

        return $this;
    }

    public function color(Color|string $color): self
    {
        $this->color = $color instanceof Color ? $color->value : $color;

        return $this;
    }

    /**
     * @param  array<string, Color|string>  $colors
     */
    public function colors(array $colors): self
    {
        $this->colors = array_map(
            static fn (Color|string $color): string => $color instanceof Color ? $color->value : $color,
            $colors,
        );

        return $this;
    }

    public function size(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @param  class-string  $enumClass
     */
    public function enum(string $enumClass): self
    {
        $this->icons = array_merge(EnumSupport::toIcons($enumClass), $this->icons);
        $this->colors = array_merge(EnumSupport::toColors($enumClass), $this->colors);

        return $this;
    }

    public function getState(Model $record): mixed
    {
        $state = parent::getState($record);

        if ($state instanceof UnitEnum) {
            return EnumSupport::valueOf($state);
        }

        return $state;
    }

    public function type(): string
    {
        return 'icon';
    }

    protected function extraProps(): array
    {
        return [
            'icon' => $this->icon,
            'icons' => $this->icons,
            'color' => $this->color,
            'colors' => $this->colors,
            'size' => $this->size,
        ];
    }
}
