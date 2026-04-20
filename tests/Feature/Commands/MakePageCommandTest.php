<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

function rmrf(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir.'/'.$entry;
        is_dir($path) ? rmrf($path) : @unlink($path);
    }

    @rmdir($dir);
}

beforeEach(function () {
    rmrf(app_path('Rocket/Pages'));
    rmrf(app_path('Rocket/Resources'));
});

it('generates a standalone page from the stub', function () {
    $exitCode = Artisan::call('rocket:make-page', ['name' => 'Settings']);

    expect($exitCode)->toBe(0);

    $path = app_path('Rocket/Pages/SettingsPage.php');
    expect($path)->toBeFile();

    $contents = file_get_contents($path);
    expect($contents)
        ->toContain('namespace App\\Rocket\\Pages;')
        ->toContain('final class SettingsPage extends Page')
        ->toContain("return 'Settings';");
});

it('generates a resource-scoped page', function () {
    $exitCode = Artisan::call('rocket:make-page', [
        'name' => 'AuditLog',
        '--resource' => 'Post',
    ]);

    expect($exitCode)->toBe(0);

    $path = app_path('Rocket/Resources/PostResource/Pages/AuditLogPage.php');
    expect($path)->toBeFile();

    $contents = file_get_contents($path);
    expect($contents)
        ->toContain('namespace App\\Rocket\\Resources\\PostResource\\Pages;')
        ->toContain('final class AuditLogPage extends Page');
});

it('fails when the page already exists', function () {
    Artisan::call('rocket:make-page', ['name' => 'Settings']);
    $exitCode = Artisan::call('rocket:make-page', ['name' => 'Settings']);

    expect($exitCode)->toBe(1);
});
