<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests\Fixtures;

use MaherElGamil\Rocket\Imports\ImportColumn;
use MaherElGamil\Rocket\Imports\Importer;

final class WidgetImporter extends Importer
{
    protected static string $model = Widget::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->guess(['name', 'widget_name'])
                ->rules(['required', 'string', 'max:255'])
                ->example('Rocket'),
            ImportColumn::make('status')
                ->guess(['status', 'state'])
                ->rules(['required', 'in:active,inactive'])
                ->example('active'),
        ];
    }

    public function resolveRecord(): ?Widget
    {
        return new Widget;
    }

    public static function getChunkSize(): int
    {
        return 10;
    }
}
