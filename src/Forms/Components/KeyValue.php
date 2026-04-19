<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class KeyValue extends Field
{
    private string $keyLabel = 'Key';

    private string $valueLabel = 'Value';

    private string $addButtonLabel = 'Add row';

    public function keyLabel(string $label): self
    {
        $this->keyLabel = $label;

        return $this;
    }

    public function valueLabel(string $label): self
    {
        $this->valueLabel = $label;

        return $this;
    }

    public function addButtonLabel(string $label): self
    {
        $this->addButtonLabel = $label;

        return $this;
    }

    public function type(): string
    {
        return 'key_value';
    }

    protected function typeRules(): array
    {
        return ['array'];
    }

    protected function extraProps(): array
    {
        return [
            'key_label' => $this->keyLabel,
            'value_label' => $this->valueLabel,
            'add_button_label' => $this->addButtonLabel,
        ];
    }

    public function getState(Model $record): mixed
    {
        $value = parent::getState($record);

        return $this->toPairs($value);
    }

    public function processSubmission(Request $request, mixed $value, ?Model $record): mixed
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = isset($row['key']) ? trim((string) $row['key']) : '';

            if ($key === '') {
                continue;
            }

            $out[$key] = isset($row['value']) ? (string) $row['value'] : '';
        }

        return $out;
    }

    /**
     * @return array<int, array{key: string, value: string}>
     */
    private function toPairs(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        $pairs = [];

        foreach ($value as $key => $val) {
            if (is_int($key) && is_array($val) && array_key_exists('key', $val)) {
                $pairs[] = [
                    'key' => (string) $val['key'],
                    'value' => (string) ($val['value'] ?? ''),
                ];

                continue;
            }

            $pairs[] = ['key' => (string) $key, 'value' => (string) $val];
        }

        return $pairs;
    }
}
