<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use MaherElGamil\Rocket\Commands\Concerns\ResolvesStubs;

final class MakePageCommand extends Command
{
    use ResolvesStubs;

    protected $signature = 'rocket:make-page {name} {--resource=}';

    protected $description = 'Create a new Rocket page class.';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));

        if (! Str::endsWith($name, 'Page')) {
            $name .= 'Page';
        }

        $resource = $this->option('resource');

        if ($resource !== null) {
            $resource = Str::studly($resource);

            if (! Str::endsWith($resource, 'Resource')) {
                $resource .= 'Resource';
            }

            $namespace = "App\\Rocket\\Resources\\{$resource}\\Pages";
            $path = app_path("Rocket/Resources/{$resource}/Pages/{$name}.php");
        } else {
            $namespace = 'App\\Rocket\\Pages';
            $path = app_path("Rocket/Pages/{$name}.php");
        }

        if (file_exists($path)) {
            $this->error("Page [{$name}] already exists.");

            return self::FAILURE;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $title = trim(Str::title(Str::snake(Str::replaceLast('Page', '', $name), ' ')));

        file_put_contents($path, $this->renderStub('page', [
            'namespace' => $namespace,
            'class' => $name,
            'title' => $title,
        ]));

        $this->info("Page [{$name}] created at {$path}.");

        return self::SUCCESS;
    }
}
