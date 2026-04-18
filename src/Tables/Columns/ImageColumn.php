<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Columns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class ImageColumn extends Column
{
    private string $disk = 'public';

    private int $size = 40;

    private bool $circular = false;

    private ?string $fallback = null;

    public function disk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function size(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function circular(bool $circular = true): self
    {
        $this->circular = $circular;

        return $this;
    }

    public function fallback(?string $url): self
    {
        $this->fallback = $url;

        return $this;
    }

    public function type(): string
    {
        return 'image';
    }

    public function render(Model $record): mixed
    {
        $state = parent::render($record);

        if ($state === null || $state === '') {
            return $this->fallback;
        }

        if (is_string($state) && (str_starts_with($state, 'http://') || str_starts_with($state, 'https://'))) {
            return $state;
        }

        return Storage::disk($this->disk)->url((string) $state);
    }

    protected function extraProps(): array
    {
        return [
            'size' => $this->size,
            'circular' => $this->circular,
        ];
    }
}
