<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use MaherElGamil\Rocket\Commands\Concerns\ResolvesStubs;

final class MakePanelCommand extends Command
{
    use ResolvesStubs;

    protected $signature = 'rocket:make-panel {name}';

    protected $description = 'Create a new Rocket panel provider.';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));

        if (! Str::endsWith($name, 'PanelProvider')) {
            $name .= 'PanelProvider';
        }

        $id = Str::of($name)
            ->replaceLast('PanelProvider', '')
            ->kebab()
            ->lower()
            ->value();

        $path = app_path("Providers/Rocket/{$name}.php");

        if (file_exists($path)) {
            $this->error("Panel provider [{$name}] already exists.");

            return self::FAILURE;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $this->renderStub('panel', [
            'namespace' => 'App\\Providers\\Rocket',
            'class' => $name,
            'id' => $id,
        ]));

        $this->info("Panel provider [{$name}] created at {$path}.");
        $this->line('');
        $this->line('Next: register it in bootstrap/providers.php:');
        $this->line("  App\\Providers\\Rocket\\{$name}::class,");

        return self::SUCCESS;
    }
}
