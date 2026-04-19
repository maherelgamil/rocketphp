<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use MaherElGamil\Rocket\Dashboard\StatWidget;
use MaherElGamil\Rocket\Pages\Actions\Action;
use MaherElGamil\Rocket\Pages\Blocks\HtmlBlock;
use MaherElGamil\Rocket\Pages\Blocks\WidgetBlock;
use MaherElGamil\Rocket\Pages\CreateRecordPage;
use MaherElGamil\Rocket\Pages\EditRecordPage;
use MaherElGamil\Rocket\Pages\ListRecordsPage;
use MaherElGamil\Rocket\Pages\Page;
use MaherElGamil\Rocket\Pages\ViewRecordPage;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

// ---------------------------------------------------------------------------
// Inline fixture page classes
// ---------------------------------------------------------------------------

class SettingsPage extends Page
{
    // slug will be: Str::kebab('SettingsPage') => 'settings-page'
}

class HiddenPage extends Page
{
    public function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

class LockedPage extends Page
{
    public function can(Request $request): bool
    {
        return false;
    }
}

class AboutPage extends Page
{
    public function getTitle(): string
    {
        return 'About Us';
    }

    public function getSubtitle(): ?string
    {
        return 'Learn more about us';
    }
}

// ---------------------------------------------------------------------------
// Helper: register a fresh panel with a unique id/path
// ---------------------------------------------------------------------------

function registerPagePanel(array $pageClasses, array $extra = []): string
{
    $uid = uniqid();
    $path = 'page-test-'.$uid;

    $panel = Panel::make('page-'.$uid)
        ->path($path)
        ->authMiddleware([])
        ->pages($pageClasses);

    app(PanelManager::class)->register($panel);

    return $path;
}

// ---------------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------------

beforeEach(function () {
    test()->actingAs(new GenericUser(['id' => 1]));
});

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('renders a registered page', function () {
    $path = registerPagePanel([SettingsPage::class]);
    $slug = (new SettingsPage)->getSlug(); // 'settings-page'

    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/pages/{$slug}");

    $response->assertOk();
    expect($response->json('component'))->toBe('rocket/Page');
});

it('returns 404 for unknown page', function () {
    $path = registerPagePanel([SettingsPage::class]);

    test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/pages/nonexistent")->assertNotFound();
});

it('returns 403 when can returns false', function () {
    $path = registerPagePanel([LockedPage::class]);
    $slug = (new LockedPage)->getSlug();

    test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/pages/{$slug}")->assertForbidden();
});

it('includes page in navigation', function () {
    $path = registerPagePanel([SettingsPage::class]);
    $slug = (new SettingsPage)->getSlug();

    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/pages/{$slug}");

    $response->assertOk();

    $navigation = $response->json('props.panel.navigation');

    expect($navigation)->not->toBeNull()
        ->and(collect($navigation)->pluck('url')->contains(fn ($url) => str_contains($url, $slug)))->toBeTrue();
});

it('excludes page from navigation when shouldRegisterNavigation is false', function () {
    $path = registerPagePanel([HiddenPage::class]);
    $slug = (new HiddenPage)->getSlug();

    // Hit a valid route on this panel — use the hidden page itself (it is still reachable)
    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/pages/{$slug}");

    $response->assertOk();

    $navigation = $response->json('props.panel.navigation');

    $hasPage = collect($navigation)->contains(fn ($item) => str_contains($item['url'] ?? '', $slug));

    expect($hasPage)->toBeFalse();
});

it('passes title and subtitle to inertia', function () {
    $path = registerPagePanel([AboutPage::class]);
    $slug = (new AboutPage)->getSlug();

    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/pages/{$slug}");

    $response->assertOk();

    expect($response->json('props.page.title'))->toBe('About Us')
        ->and($response->json('props.page.subtitle'))->toBe('Learn more about us');
});

it('derives slug from class name', function () {
    expect((new SettingsPage)->getSlug())->toBe('settings-page');

    $path = registerPagePanel([SettingsPage::class]);

    test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/pages/settings-page")->assertOk();
});

// ---------------------------------------------------------------------------
// Action fixture page
// ---------------------------------------------------------------------------

class ActionTestPage extends Page
{
    public static bool $callbackRan = false;

    /**
     * @return array<int, Action>
     */
    public function actions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->action(function () {
                    ActionTestPage::$callbackRan = true;
                }),
        ];
    }
}

it('serializes actions in page props', function () {
    $path = registerPagePanel([ActionTestPage::class]);
    $slug = (new ActionTestPage)->getSlug();

    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/pages/{$slug}");

    $response->assertOk();

    $actions = $response->json('props.actions');

    expect($actions)->toBeArray()
        ->and($actions[0]['name'])->toBe('refresh')
        ->and($actions[0]['label'])->toBe('Refresh');
});

it('executes a page action via post', function () {
    ActionTestPage::$callbackRan = false;

    $path = registerPagePanel([ActionTestPage::class]);
    $slug = (new ActionTestPage)->getSlug();

    $response = test()->post("/{$path}/pages/{$slug}/actions/refresh");

    $response->assertRedirect();

    expect(ActionTestPage::$callbackRan)->toBeTrue();
});

it('returns 404 for unknown action', function () {
    $path = registerPagePanel([ActionTestPage::class]);
    $slug = (new ActionTestPage)->getSlug();

    test()->post("/{$path}/pages/{$slug}/actions/nonexistent")->assertNotFound();
});

// ---------------------------------------------------------------------------
// Block fixture pages
// ---------------------------------------------------------------------------

class HtmlBlockPage extends Page
{
    public function content(Panel $panel): array
    {
        return [new HtmlBlock('<p>Hello</p>')];
    }
}

class WidgetBlockPage extends Page
{
    public function content(Panel $panel): array
    {
        return [new WidgetBlock(new StatWidget('Users', 42))];
    }
}

// ---------------------------------------------------------------------------
// Block serialization tests
// ---------------------------------------------------------------------------

it('it_serializes_html_block_in_content', function () {
    $path = registerPagePanel([HtmlBlockPage::class]);
    $slug = (new HtmlBlockPage)->getSlug();

    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/pages/{$slug}");

    $response->assertOk();

    $content = $response->json('props.content');

    expect($content[0]['type'])->toBe('html')
        ->and($content[0]['html'])->toBe('<p>Hello</p>');
});

it('it_serializes_widget_block_in_content', function () {
    $path = registerPagePanel([WidgetBlockPage::class]);
    $slug = (new WidgetBlockPage)->getSlug();

    $response = test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get("/{$path}/pages/{$slug}");

    $response->assertOk();

    $content = $response->json('props.content');

    expect($content[0]['type'])->toBe('widget')
        ->and($content[0]['widget']['type'])->toBe('stat');
});

// ---------------------------------------------------------------------------
// discoverPages() auto-discovery tests
// ---------------------------------------------------------------------------

/**
 * Make a temp directory unique to this test and register cleanup.
 */
function makeDiscoveryTempDir(): string
{
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rocket-discover-'.uniqid('', true);
    mkdir($dir, 0777, true);

    return realpath($dir);
}

function cleanupDiscoveryTempDir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $full = $dir.DIRECTORY_SEPARATOR.$entry;
        if (is_dir($full)) {
            cleanupDiscoveryTempDir($full);
        } else {
            @unlink($full);
        }
    }
    @rmdir($dir);
}

it('it_discovers_pages_in_a_directory', function () {
    $dir = makeDiscoveryTempDir();
    $ns = 'Rocket\\DiscoveryTest\\'.uniqid('N');

    $file = $dir.DIRECTORY_SEPARATOR.'MyDiscoveredPage.php';
    file_put_contents($file, <<<PHP
<?php
namespace {$ns};
use MaherElGamil\\Rocket\\Pages\\Page;
class MyDiscoveredPage extends Page {}
PHP);

    require_once $file;

    $panel = Panel::make('discover-test-'.uniqid());
    $panel->discoverPages($dir, $ns);

    expect($panel->getPages())->toContain($ns.'\\MyDiscoveredPage');

    cleanupDiscoveryTempDir($dir);
});

it('it_ignores_non_page_classes_during_discovery', function () {
    $dir = makeDiscoveryTempDir();
    $ns = 'Rocket\\DiscoveryTest\\'.uniqid('N');

    $file = $dir.DIRECTORY_SEPARATOR.'PlainClass.php';
    file_put_contents($file, <<<PHP
<?php
namespace {$ns};
class PlainClass {}
PHP);

    require_once $file;

    $panel = Panel::make('discover-test-'.uniqid());
    $panel->discoverPages($dir, $ns);

    expect($panel->getPages())->toBeEmpty();

    cleanupDiscoveryTempDir($dir);
});

it('it_ignores_abstract_pages_during_discovery', function () {
    $dir = makeDiscoveryTempDir();
    $ns = 'Rocket\\DiscoveryTest\\'.uniqid('N');

    $file = $dir.DIRECTORY_SEPARATOR.'AbstractPage.php';
    file_put_contents($file, <<<PHP
<?php
namespace {$ns};
use MaherElGamil\\Rocket\\Pages\\Page;
abstract class AbstractPage extends Page {}
PHP);

    require_once $file;

    $panel = Panel::make('discover-test-'.uniqid());
    $panel->discoverPages($dir, $ns);

    expect($panel->getPages())->toBeEmpty();

    cleanupDiscoveryTempDir($dir);
});

it('it_returns_early_for_nonexistent_directory', function () {
    $panel = Panel::make('discover-test-'.uniqid());

    $result = $panel->discoverPages('/nonexistent/path/'.uniqid(), 'X');

    expect($result)->toBe($panel)
        ->and($panel->getPages())->toBeEmpty();
});

it('it_combines_manual_and_discovered_pages', function () {
    $dir = makeDiscoveryTempDir();
    $ns = 'Rocket\\DiscoveryTest\\'.uniqid('N');

    $file = $dir.DIRECTORY_SEPARATOR.'DiscoveredOne.php';
    file_put_contents($file, <<<PHP
<?php
namespace {$ns};
use MaherElGamil\\Rocket\\Pages\\Page;
class DiscoveredOne extends Page {}
PHP);

    require_once $file;

    $discoveredClass = $ns.'\\DiscoveredOne';

    $panel = Panel::make('discover-test-'.uniqid())
        ->pages([SettingsPage::class])
        ->discoverPages($dir, $ns);

    // Call discoverPages twice to verify de-duplication.
    $panel->discoverPages($dir, $ns);

    $pages = $panel->getPages();

    expect($pages)->toContain(SettingsPage::class)
        ->and($pages)->toContain($discoveredClass)
        ->and(count(array_filter($pages, fn ($p) => $p === $discoveredClass)))->toBe(1);

    cleanupDiscoveryTempDir($dir);
});

// ---------------------------------------------------------------------------
// Resource custom page discovery
// ---------------------------------------------------------------------------

it('discovers_custom_resource_pages_from_directory', function () {
    $dir = makeDiscoveryTempDir();
    $ns = 'MaherElGamil\\Rocket\\Tests\\Fixtures\\Discover'.uniqid('N');
    $resourceClass = WidgetResource::class;

    // Create a custom page class in temp dir; the file's path relative to
    // $dir must match the class FQN so Resource::discoverPages can load it.
    $nsPath = str_replace('\\', DIRECTORY_SEPARATOR, $ns);
    @mkdir($dir.DIRECTORY_SEPARATOR.$nsPath, 0777, true);
    $file = $dir.DIRECTORY_SEPARATOR.$nsPath.DIRECTORY_SEPARATOR.'StatsPage.php';
    file_put_contents($file, <<<PHP
<?php
namespace {$ns};
use MaherElGamil\\Rocket\\Pages\\ResourcePage;
class StatsPage extends ResourcePage
{
    public function getSlug(): string
    {
        return 'stats';
    }

    public function getTitle(): string
    {
        return 'Widget Stats';
    }
}
PHP);

    require_once $file;

    $pages = $resourceClass::discoverPages($dir);

    expect($pages)->toHaveKey('stats')
        ->and($pages['stats'])->toBe($ns.'\\StatsPage');

    unlink($file);
});

it('discovered_pages_override_standard_crud_pages', function () {
    $dir = makeDiscoveryTempDir();
    $ns = 'MaherElGamil\\Rocket\\Tests\\Fixtures\\Discover'.uniqid('N');
    $resourceClass = WidgetResource::class;

    $nsPath = str_replace('\\', DIRECTORY_SEPARATOR, $ns);
    @mkdir($dir.DIRECTORY_SEPARATOR.$nsPath, 0777, true);
    $file = $dir.DIRECTORY_SEPARATOR.$nsPath.DIRECTORY_SEPARATOR.'CustomListRecordsPage.php';
    file_put_contents($file, <<<PHP
<?php
namespace {$ns};
use MaherElGamil\\Rocket\\Pages\\ResourcePage;
class CustomListRecordsPage extends ResourcePage
{
    public function getSlug(): string
    {
        return 'index';
    }
}
PHP);

    require_once $file;

    $pages = $resourceClass::discoverPages($dir);

    expect($pages)->toHaveKey('index')
        ->and($pages['index'])->toBe($ns.'\\CustomListRecordsPage');

    unlink($file);
});

it('getPages_returns_standard_crud_pages', function () {
    $resourceClass = WidgetResource::class;

    $pages = $resourceClass::getPages();

    expect($pages)->toHaveKey('index')
        ->toHaveKey('create')
        ->toHaveKey('edit')
        ->toHaveKey('view')
        ->and($pages['index'])->toBe(ListRecordsPage::class)
        ->and($pages['create'])->toBe(CreateRecordPage::class)
        ->and($pages['edit'])->toBe(EditRecordPage::class)
        ->and($pages['view'])->toBe(ViewRecordPage::class);
});

it('returns_empty_array_for_nonexistent_resource_page_directory', function () {
    $resourceClass = WidgetResource::class;

    $pages = $resourceClass::discoverPages('/nonexistent/path/'.uniqid());

    expect($pages)->toBeEmpty();
});
