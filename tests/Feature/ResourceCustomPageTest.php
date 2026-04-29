<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use MaherElGamil\Rocket\Pages\Actions\Action;
use MaherElGamil\Rocket\Pages\Blocks\HtmlBlock;
use MaherElGamil\Rocket\Pages\CreateRecordPage;
use MaherElGamil\Rocket\Pages\ListRecordsPage;
use MaherElGamil\Rocket\Pages\ResourcePage;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tests\Fixtures\OpenWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

// ---------------------------------------------------------------------------
// Inline fixture: custom ResourcePage with known slug
// ---------------------------------------------------------------------------

class CustomStatsPage extends ResourcePage
{
    public static bool $actionRan = false;

    public function getTitle(): string
    {
        return 'Widget Stats';
    }

    public function content(Panel $panel): array
    {
        return [new HtmlBlock('<p>Stats</p>')];
    }

    public function actions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->action(function () {
                    CustomStatsPage::$actionRan = true;
                }),
        ];
    }
}

/**
 * A resource that ships a known custom page (bypasses filesystem discovery).
 */
class CustomPageWidgetResource extends WidgetResource
{
    protected static ?string $slug = 'cp-widgets';

    protected static function discoverPagesInApp(): array
    {
        return ['stats' => CustomStatsPage::class];
    }
}

/**
 * A resource whose "discovered" page tries to use a reserved slug — should be skipped.
 */
class ReservedSlugResource extends WidgetResource
{
    protected static ?string $slug = 'rs-widgets';

    protected static function discoverPagesInApp(): array
    {
        // The Resource itself simulates discovery; apply the same
        // reserved-slug filter the real discoverer does.
        $candidates = [
            'index' => CustomStatsPage::class,
            'create' => CustomStatsPage::class,
            'stats' => CustomStatsPage::class,
        ];

        $reserved = ['index', 'create', 'edit', 'view'];

        return array_filter(
            $candidates,
            fn (string $key) => ! in_array($key, $reserved, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}

// ---------------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------------

beforeEach(function () {
    Gate::policy(Widget::class, OpenWidgetPolicy::class);
    test()->actingAs(new GenericUser(['id' => 1]));

    Schema::dropIfExists('widgets');
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->default('active');
        $table->boolean('is_featured')->default(false);
        $table->date('published_at')->nullable();
        $table->softDeletes();
    });

    app(PanelManager::class)->register(
        Panel::make('cp-test')
            ->path('cp-admin')
            ->authMiddleware([])
            ->resources([CustomPageWidgetResource::class, ReservedSlugResource::class])
    );
});

function inertiaGetCp(string $uri): TestResponse
{
    return test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get($uri);
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('resolves a custom resource page by slug', function () {
    $response = inertiaGetCp('/cp-admin/cp-widgets/stats')->assertOk();

    expect($response->json('component'))->toBe('rocket/page')
        ->and($response->json('props.page.title'))->toBe('Widget Stats')
        ->and($response->json('props.content.0.type'))->toBe('html');
});

it('returns 404 for unknown custom page slug', function () {
    inertiaGetCp('/cp-admin/cp-widgets/nonexistent')->assertNotFound();
});

it('includes the custom page in resource getPages output', function () {
    $pages = CustomPageWidgetResource::getPages();

    expect($pages)->toHaveKey('stats')
        ->and($pages['stats'])->toBe(CustomStatsPage::class)
        ->and($pages)->toHaveKey('index')
        ->and($pages)->toHaveKey('create')
        ->and($pages)->toHaveKey('edit')
        ->and($pages)->toHaveKey('view');
});

it('skips built-in keys when discovering custom pages', function () {
    $pages = ReservedSlugResource::getPages();

    // index/create/edit/view must still map to the built-ins, not the custom page.
    expect($pages['index'])->toBe(ListRecordsPage::class)
        ->and($pages['create'])->toBe(CreateRecordPage::class)
        ->and($pages)->toHaveKey('stats')
        ->and($pages['stats'])->toBe(CustomStatsPage::class);
});

it('executes a custom resource page action', function () {
    CustomStatsPage::$actionRan = false;

    test()->post('/cp-admin/cp-widgets/stats/actions/refresh')
        ->assertRedirect();

    expect(CustomStatsPage::$actionRan)->toBeTrue();
});

it('returns 404 for unknown custom page action', function () {
    test()->post('/cp-admin/cp-widgets/stats/actions/nonexistent')
        ->assertNotFound();
});

it('derives default custom page slug from class name', function () {
    expect((new CustomStatsPage)->getCustomPageSlug())->toBe('custom-stats');
});

// ---------------------------------------------------------------------------
// Filesystem discovery test
// ---------------------------------------------------------------------------

it('discovers custom resource pages from the app directory', function () {
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rocket-app-discover-'.uniqid('', true);
    $pagesDir = $dir.DIRECTORY_SEPARATOR.'Rocket'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'DiscoveredResource'.DIRECTORY_SEPARATOR.'Pages';
    mkdir($pagesDir, 0777, true);

    $ns = 'App\\Rocket\\Resources\\DiscoveredResource\\Pages';
    $file = $pagesDir.DIRECTORY_SEPARATOR.'ReportPage.php';

    file_put_contents($file, <<<PHP
<?php
namespace {$ns};
use MaherElGamil\\Rocket\\Pages\\ResourcePage;
class ReportPage extends ResourcePage {}
PHP);

    require_once $file;

    // Define a resource class whose basename is "DiscoveredResource" so the
    // discovery path maps to {tempDir}/Rocket/Resources/DiscoveredResource/Pages.
    $resourceNs = 'Rocket\\DiscoveryTmp'.uniqid('N');
    $resourceFqcn = $resourceNs.'\\DiscoveredResource';

    if (! class_exists($resourceFqcn)) {
        eval("namespace {$resourceNs}; class DiscoveredResource extends \\MaherElGamil\\Rocket\\Tests\\Fixtures\\WidgetResource { protected static ?string \$slug = 'discovered-".uniqid()."'; }");
    }

    // Point Laravel's app_path() helper to our temp dir.
    $app = app();
    $original = $app->path();
    $app->useAppPath($dir);

    try {
        $expectedClass = $ns.'\\ReportPage';
        $pages = $resourceFqcn::getPages();

        expect($pages)->toHaveKey('report')
            ->and($pages['report'])->toBe($expectedClass);
    } finally {
        $app->useAppPath($original);
        @unlink($file);
    }
});
