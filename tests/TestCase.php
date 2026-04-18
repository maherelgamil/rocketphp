<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tests;

use Inertia\ServiceProvider as InertiaServiceProvider;
use MaherElGamil\Rocket\RocketServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
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
