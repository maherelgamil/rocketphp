<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use MaherElGamil\Rocket\Exports\ExportColumn;
use MaherElGamil\Rocket\Exports\Exporter;

final class WidgetExporter extends Exporter
{
    protected static string $model = Widget::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('name')->label('Name'),
            ExportColumn::make('status')->label('Status'),
        ];
    }

    public static function getChunkSize(): int
    {
        return 10;
    }
}
