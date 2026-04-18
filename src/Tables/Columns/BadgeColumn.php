<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Columns;

use Illuminate\Database\Eloquent\Model;
use MaherElGamil\Rocket\Support\Color;
use MaherElGamil\Rocket\Support\EnumSupport;
use UnitEnum;

final class BadgeColumn extends Column
{
    /** @var array<string, string> */
    private array $colors = [];

    /** @var class-string|null */
    private ?string $enumClass = null;

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

    /**
     * @param  class-string  $enumClass
     */
    public function enum(string $enumClass): self
    {
        $this->enumClass = $enumClass;
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

    public function render(Model $record): mixed
    {
        $state = parent::render($record);

        if ($this->enumClass !== null && $state !== null && enum_exists($this->enumClass)) {
            $case = $this->resolveCase($state);

            if ($case !== null) {
                return EnumSupport::labelOf($case);
            }
        }

        return $state;
    }

    public function type(): string
    {
        return 'badge';
    }

    protected function extraProps(): array
    {
        return [
            'colors' => $this->colors,
        ];
    }

    private function resolveCase(mixed $state): ?UnitEnum
    {
        if ($state instanceof UnitEnum) {
            return $state;
        }

        $enumClass = $this->enumClass;

        if ($enumClass === null) {
            return null;
        }

        foreach ($enumClass::cases() as $case) {
            if (EnumSupport::valueOf($case) === (string) $state) {
                return $case;
            }
        }

        return null;
    }
}
