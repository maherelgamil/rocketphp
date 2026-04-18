<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Support;

use BackedEnum;
use InvalidArgumentException;
use MaherElGamil\Rocket\Support\Color;
use MaherElGamil\Rocket\Support\Contracts\HasColor;
use MaherElGamil\Rocket\Support\Contracts\HasLabel;
use UnitEnum;

final class EnumSupport
{
    /**
     * @param  class-string  $enumClass
     * @return array<string, string>
     */
    public static function toOptions(string $enumClass): array
    {
        self::assertEnum($enumClass);

        $options = [];

        foreach ($enumClass::cases() as $case) {
            $options[self::valueOf($case)] = self::labelOf($case);
        }

        return $options;
    }

    /**
     * @param  class-string  $enumClass
     * @return array<string, string>
     */
    public static function toColors(string $enumClass): array
    {
        self::assertEnum($enumClass);

        $colors = [];

        foreach ($enumClass::cases() as $case) {
            if ($case instanceof HasColor) {
                $color = $case->getColor();

                if ($color instanceof Color) {
                    $color = $color->value;
                }

                if ($color !== null) {
                    $colors[self::valueOf($case)] = $color;
                }
            }
        }

        return $colors;
    }

    public static function valueOf(UnitEnum $case): string
    {
        return $case instanceof BackedEnum ? (string) $case->value : $case->name;
    }

    public static function labelOf(UnitEnum $case): string
    {
        if ($case instanceof HasLabel) {
            return $case->getLabel();
        }

        return $case->name;
    }

    /**
     * @param  class-string  $enumClass
     */
    private static function assertEnum(string $enumClass): void
    {
        if (! enum_exists($enumClass)) {
            throw new InvalidArgumentException("[{$enumClass}] is not a valid enum.");
        }
    }
}
