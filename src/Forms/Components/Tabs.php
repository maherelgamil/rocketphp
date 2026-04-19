<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

use Illuminate\Database\Eloquent\Model;

final class Tabs
{
    /** @var array<int, Section> */
    private array $tabs = [];

    /**
     * @param  array<int, Section>  $tabs
     */
    public function schema(array $tabs): self
    {
        $this->tabs = $tabs;

        return $this;
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * @return array<int, Section>
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /**
     * @return array<int, Field>
     */
    public function getFields(): array
    {
        $fields = [];

        foreach ($this->tabs as $tab) {
            foreach ($tab->getFields() as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(?Model $record = null): array
    {
        return [
            'type' => 'tabs',
            'tabs' => array_map(
                static fn (Section $tab) => $tab->toArray($record),
                $this->tabs,
            ),
        ];
    }
}
