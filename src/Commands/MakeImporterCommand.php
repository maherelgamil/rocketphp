<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use MaherElGamil\Rocket\Commands\Concerns\ResolvesStubs;

final class MakeImporterCommand extends Command
{
    use ResolvesStubs;

    protected $signature = 'rocket:make-importer {name} {--model=}';

    protected $description = 'Create a new Rocket importer class.';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));

        if (! Str::endsWith($name, 'Importer')) {
            $name .= 'Importer';
        }

        $model = (string) ($this->option('model') ?? Str::replaceLast('Importer', '', $name));
        $modelFqcn = Str::contains($model, '\\') ? $model : "App\\Models\\{$model}";

        $path = app_path("Rocket/Imports/{$name}.php");

        if (file_exists($path)) {
            $this->error("Importer [{$name}] already exists.");

            return self::FAILURE;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $this->renderStub('importer', [
            'namespace' => 'App\\Rocket\\Imports',
            'class' => $name,
            'model' => class_basename($modelFqcn),
            'modelFqcn' => ltrim($modelFqcn, '\\'),
        ]));

        $this->info("Importer [{$name}] created at {$path}.");

        return self::SUCCESS;
    }
}
