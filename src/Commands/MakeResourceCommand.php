<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class MakeResourceCommand extends Command
{
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

        $stub = $this->stub($name, $modelFqcn);

        file_put_contents($path, $stub);

        $this->info("Resource [{$name}] created at {$path}.");

        return self::SUCCESS;
    }

    private function stub(string $class, string $modelFqcn): string
    {
        $model = class_basename($modelFqcn);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Rocket\Resources;

use {$modelFqcn};
use MaherElGamil\\Rocket\\Resources\\Resource;
use MaherElGamil\\Rocket\\Tables\\Columns\\TextColumn;
use MaherElGamil\\Rocket\\Tables\\Table;

final class {$class} extends Resource
{
    protected static string \$model = {$model}::class;

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
                TextColumn::make('id')->sortable(),
            ])
            ->searchable(['id']);
    }
}

PHP;
    }
}
