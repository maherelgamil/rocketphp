<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tests\Fixtures\Author;
use MaherElGamil\Rocket\Tests\Fixtures\Comment;
use MaherElGamil\Rocket\Tests\Fixtures\DenyViewAnyCommentPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\OpenCommentPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\OpenWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetWithRelationsResource;

beforeEach(function () {
    Gate::policy(Widget::class, OpenWidgetPolicy::class);
    Gate::policy(Comment::class, OpenCommentPolicy::class);
    Gate::policy(Author::class, OpenCommentPolicy::class);
    test()->actingAs(new GenericUser(['id' => 1]));

    Schema::dropIfExists('widgets');
    Schema::dropIfExists('comments');
    Schema::dropIfExists('authors');
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->default('active');
        $table->boolean('is_featured')->default(false);
        $table->date('published_at')->nullable();
        $table->softDeletes();
    });
    Schema::create('comments', function ($table) {
        $table->id();
        $table->foreignId('widget_id');
        $table->string('body');
        $table->string('status')->default('pending');
    });
    Schema::create('authors', function ($table) {
        $table->id();
        $table->foreignId('widget_id');
        $table->string('name');
    });
});

function registerPanel(array $resources): void
{
    app(PanelManager::class)->register(
        Panel::make('test-rm-'.uniqid())
            ->path('test-rm')
            ->authMiddleware([])
            ->resources($resources)
    );
}

function relInertiaGet(string $uri): TestResponse
{
    return test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'rocket',
    ])->get($uri);
}

it('renders no panels when the resource declares no relation managers', function () {
    registerPanel([WidgetResource::class]);
    $widget = Widget::create(['name' => 'w1']);

    $payload = relInertiaGet('/test-rm/widgets/'.$widget->getKey().'/edit')->json();

    expect($payload['props']['relation_managers'])->toBe([]);
});

it('renders a declared relation manager with rows scoped to the owner record', function () {
    registerPanel([WidgetWithRelationsResource::class]);
    $a = Widget::create(['name' => 'a']);
    $b = Widget::create(['name' => 'b']);
    Comment::insert([
        ['widget_id' => $a->id, 'body' => 'First', 'status' => 'approved'],
        ['widget_id' => $a->id, 'body' => 'Second', 'status' => 'pending'],
        ['widget_id' => $b->id, 'body' => 'Other', 'status' => 'approved'],
    ]);

    $payload = relInertiaGet('/test-rm/widgets-with-relations/'.$a->id.'/edit')->json();

    $managers = $payload['props']['relation_managers'];
    expect($managers)->toHaveKey('comments');
    expect($managers['comments']['records'])->toHaveCount(2);
    expect($managers['comments']['pagination']['total'])->toBe(2);
    expect($managers['comments']['title'])->toBe('Comments');
    expect($managers['comments']['prefix'])->toBe('rm_comments_');
});

it('namespaces search / sort between managers so they do not collide', function () {
    registerPanel([WidgetWithRelationsResource::class]);
    $w = Widget::create(['name' => 'w']);
    Comment::insert([
        ['widget_id' => $w->id, 'body' => 'Alpha', 'status' => 'approved'],
        ['widget_id' => $w->id, 'body' => 'Beta', 'status' => 'pending'],
    ]);
    Author::insert([
        ['widget_id' => $w->id, 'name' => 'Zed'],
        ['widget_id' => $w->id, 'name' => 'Amy'],
    ]);

    $payload = relInertiaGet(
        '/test-rm/widgets-with-relations/'.$w->id.'/edit?rm_comments_search=Beta&rm_authors_sort=name&rm_authors_direction=desc'
    )->json();

    $comments = $payload['props']['relation_managers']['comments'];
    $authors = $payload['props']['relation_managers']['authors'];

    expect($comments['records'])->toHaveCount(1);
    expect($comments['records'][0]['body'])->toBe('Beta');
    expect($comments['filters']['search'])->toBe('Beta');
    expect($authors['filters']['search'])->toBe('');
    expect(array_column($authors['records'], 'name'))->toBe(['Zed', 'Amy']);
});

it('paginates each manager under its own page key', function () {
    registerPanel([WidgetWithRelationsResource::class]);
    $w = Widget::create(['name' => 'w']);
    $rows = [];
    for ($i = 1; $i <= 30; $i++) {
        $rows[] = ['widget_id' => $w->id, 'body' => 'c'.$i, 'status' => 'approved'];
    }
    Comment::insert($rows);

    $payload = relInertiaGet(
        '/test-rm/widgets-with-relations/'.$w->id.'/edit?rm_comments_page=2&rm_comments_per_page=10'
    )->json();

    $comments = $payload['props']['relation_managers']['comments'];
    expect($comments['pagination']['current_page'])->toBe(2);
    expect($comments['pagination']['total'])->toBe(30);
    expect($comments['page_key'])->toBe('rm_comments_page');
});

it('applies manager filters via the scoped query key', function () {
    registerPanel([WidgetWithRelationsResource::class]);
    $w = Widget::create(['name' => 'w']);
    Comment::insert([
        ['widget_id' => $w->id, 'body' => 'One', 'status' => 'approved'],
        ['widget_id' => $w->id, 'body' => 'Two', 'status' => 'pending'],
    ]);

    $payload = relInertiaGet(
        '/test-rm/widgets-with-relations/'.$w->id.'/edit?rm_comments_filter_status=pending'
    )->json();

    $comments = $payload['props']['relation_managers']['comments'];
    expect($comments['records'])->toHaveCount(1);
    expect($comments['records'][0]['body'])->toBe('Two');

    $statusFilter = collect($comments['table_filters'])->firstWhere('name', 'status');
    expect($statusFilter['query_key'])->toBe('rm_comments_filter_status');
});

it('hides a manager when the related model denies viewAny', function () {
    Gate::policy(Comment::class, DenyViewAnyCommentPolicy::class);
    registerPanel([WidgetWithRelationsResource::class]);
    $w = Widget::create(['name' => 'w']);
    Comment::insert([['widget_id' => $w->id, 'body' => 'x', 'status' => 'approved']]);

    $payload = relInertiaGet('/test-rm/widgets-with-relations/'.$w->id.'/edit')->json();

    $managers = $payload['props']['relation_managers'];
    expect($managers)->not->toHaveKey('comments');
    expect($managers)->toHaveKey('authors');
});

it('exposes the relation_managers_layout prop with a default of tabs', function () {
    registerPanel([WidgetWithRelationsResource::class]);
    $w = Widget::create(['name' => 'w']);

    $payload = relInertiaGet('/test-rm/widgets-with-relations/'.$w->id.'/edit')->json();

    expect($payload['props']['relation_managers_layout'])->toBe('tabs');
});

it('exposes the full request query on edit and view pages so managers preserve siblings on navigation', function () {
    registerPanel([WidgetWithRelationsResource::class]);
    $w = Widget::create(['name' => 'w']);
    Comment::insert([['widget_id' => $w->id, 'body' => 'x', 'status' => 'approved']]);

    $payload = relInertiaGet(
        '/test-rm/widgets-with-relations/'.$w->id.'/edit?rm_comments_search=x&rm_authors_sort=name'
    )->json();

    expect($payload['props']['query'])->toMatchArray([
        'rm_comments_search' => 'x',
        'rm_authors_sort' => 'name',
    ]);
});

it('defaults the manager per_page to the rocket.pagination.relation_manager.per_page config', function () {
    config()->set('rocket.pagination.relation_manager.per_page', 5);
    registerPanel([WidgetWithRelationsResource::class]);
    $w = Widget::create(['name' => 'w']);
    $rows = [];
    for ($i = 1; $i <= 12; $i++) {
        $rows[] = ['widget_id' => $w->id, 'body' => 'c'.$i, 'status' => 'approved'];
    }
    Comment::insert($rows);

    $payload = relInertiaGet('/test-rm/widgets-with-relations/'.$w->id.'/edit')->json();

    expect($payload['props']['relation_managers']['comments']['filters']['per_page'])->toBe(5);
    expect($payload['props']['relation_managers']['comments']['records'])->toHaveCount(5);
});

it('applies the manager default sort when no sort query is present', function () {
    registerPanel([WidgetWithRelationsResource::class]);
    $w = Widget::create(['name' => 'w']);
    Comment::insert([
        ['widget_id' => $w->id, 'body' => 'first', 'status' => 'approved'],
        ['widget_id' => $w->id, 'body' => 'second', 'status' => 'approved'],
        ['widget_id' => $w->id, 'body' => 'third', 'status' => 'approved'],
    ]);

    $payload = relInertiaGet('/test-rm/widgets-with-relations/'.$w->id.'/edit')->json();

    $ids = array_map('intval', array_column($payload['props']['relation_managers']['comments']['records'], 'id'));
    expect($ids)->toBe([3, 2, 1]);
});
