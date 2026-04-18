<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Forms\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class FileUpload extends Field
{
    private ?string $disk = null;

    private string $directory = 'uploads';

    private bool $image = false;

    /** @var array<int, string> */
    private array $acceptedMimes = [];

    private ?int $maxKilobytes = null;

    public function disk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function directory(string $directory): self
    {
        $this->directory = trim($directory, '/');

        return $this;
    }

    public function image(bool $image = true): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @param  array<int, string>  $mimes
     */
    public function acceptedMimes(array $mimes): self
    {
        $this->acceptedMimes = array_values($mimes);

        return $this;
    }

    public function maxSize(int $kilobytes): self
    {
        $this->maxKilobytes = max(1, $kilobytes);

        return $this;
    }

    public function type(): string
    {
        return 'file';
    }

    protected function typeRules(): array
    {
        $rules = [$this->image ? 'image' : 'file'];

        if ($this->acceptedMimes !== []) {
            $rules[] = 'mimes:'.implode(',', $this->acceptedMimes);
        }

        if ($this->maxKilobytes !== null) {
            $rules[] = "max:{$this->maxKilobytes}";
        }

        return $rules;
    }

    protected function extraProps(): array
    {
        return [
            'image' => $this->image,
            'accepted_mimes' => $this->acceptedMimes,
            'max_kilobytes' => $this->maxKilobytes,
            'directory' => $this->directory,
        ];
    }

    public function getState(Model $record): mixed
    {
        return null;
    }

    public function toArray(?Model $record = null): array
    {
        $payload = parent::toArray($record);
        $payload['extra']['current'] = $record !== null
            ? data_get($record, $this->name)
            : null;

        return $payload;
    }

    public function processSubmission(Request $request, mixed $value, ?Model $record): mixed
    {
        if ($request->hasFile($this->name)) {
            $disk = $this->disk ?? config('rocket.uploads.disk', 'public');

            return $request->file($this->name)->store($this->directory, $disk);
        }

        return Field::SKIP;
    }
}
