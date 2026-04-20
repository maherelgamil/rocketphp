<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use MaherElGamil\Rocket\Commands\Concerns\ResolvesStubs;

final class MakeResourceCommand extends Command
{
    use ResolvesStubs;

    protected $signature = 'rocket:make-resource {name} {--model=}';

    protected $description = 'Create a new Rocket resource class.';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));

        if (! Str::endsWith($name, 'Resource')) {
            $name .= 'Resource';
        }

        $model = (string) ($this->option('model') ?? Str::replaceLast('Resource', '', $name));
        $modelFqcn = Str::contains($model, '\\') ? $model : "App\\Models\\{$model}";

        $path = app_path("Rocket/Resources/{$name}.php");

        if (file_exists($path)) {
            $this->error("Resource [{$name}] already exists.");

            return self::FAILURE;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $this->renderStub('resource', [
            'namespace' => 'App\\Rocket\\Resources',
            'class' => $name,
            'model' => class_basename($modelFqcn),
            'modelFqcn' => ltrim($modelFqcn, '\\'),
        ]));

        $this->info("Resource [{$name}] created at {$path}.");

        return self::SUCCESS;
    }
}
