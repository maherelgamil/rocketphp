<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests;

use Illuminate\Foundation\Application;
use Inertia\ServiceProvider as InertiaServiceProvider;
use MaherElGamil\Rocket\RocketServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            RocketServiceProvider::class,
        ];
    }
}
