<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Commands\Concerns;

use RuntimeException;

trait ResolvesStubs
{
    /**
     * @param  array<string, string>  $replacements
     */
    protected function renderStub(string $name, array $replacements): string
    {
        $contents = file_get_contents($this->stubPath($name));

        if ($contents === false) {
            throw new RuntimeException("Unable to read stub [{$name}].");
        }

        foreach ($replacements as $key => $value) {
            $contents = str_replace('{{ '.$key.' }}', $value, $contents);
        }

        return $contents;
    }

    private function stubPath(string $name): string
    {
        $published = base_path("stubs/rocket/{$name}.stub");

        if (file_exists($published)) {
            return $published;
        }

        $packaged = __DIR__.'/../../../stubs/'.$name.'.stub';

        if (! file_exists($packaged)) {
            throw new RuntimeException("Stub [{$name}] not found.");
        }

        return $packaged;
    }
}
