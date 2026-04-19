<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Dashboard;

use MaherElGamil\Rocket\Resources\Resource;
use MaherElGamil\Rocket\Tables\Table;

final class RecentRecordsWidget
{
    /** @var class-string<resource>|null */
    private ?string $resource = null;

    private int $limit = 5;

    public function __construct(private readonly string $title) {}

    public static function make(string $title): self
    {
        return new self($title);
    }

    /**
     * @param  class-string<resource>  $resource
     */
    public function resource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->resource === null) {
            return ['type' => 'recent_records', 'title' => $this->title, 'columns' => [], 'rows' => [], 'resource_url' => null];
        }

        $resource = $this->resource;
        $table = $resource::table(Table::make($resource));

        $records = $resource::query()
            ->latest()
            ->limit($this->limit)
            ->get();

        $columns = array_map(
            fn ($col) => ['name' => $col->getName(), 'label' => $col->getLabel()],
            $table->getColumns(),
        );

        $rows = $records->map(function ($record) use ($table) {
            return $table->renderRow($record);
        })->all();

        return [
            'type' => 'recent_records',
            'title' => $this->title,
            'columns' => $columns,
            'rows' => $rows,
            'resource_url' => $resource::getSlug(),
        ];
    }
}
