<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use MaherElGamil\Rocket\Exports\Jobs\CompleteExportJob;
use MaherElGamil\Rocket\Exports\Jobs\ExportCsvChunkJob;
use MaherElGamil\Rocket\Exports\Jobs\PrepareExportJob;
use MaherElGamil\Rocket\Models\Export;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use MaherElGamil\Rocket\Tables\Actions\ExportAction;
use MaherElGamil\Rocket\Tables\Actions\ExportBulkAction;
use MaherElGamil\Rocket\Tests\Fixtures\OpenWidgetPolicy;
use MaherElGamil\Rocket\Tests\Fixtures\Widget;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetExporter;
use MaherElGamil\Rocket\Tests\Fixtures\WidgetResource;

beforeEach(function () {
    Gate::policy(Widget::class, OpenWidgetPolicy::class);
    test()->actingAs(new GenericUser(['id' => 1]));

    Storage::fake('local');
    config()->set('rocket.exports.disk', 'local');
    config()->set('rocket.exports.chunk_size', 10);
    config()->set('queue.default', 'sync');
    config()->set('queue.batching.database', config('database.default'));
    config()->set('queue.batching.table', 'job_batches');

    Schema::dropIfExists('widgets');
    Schema::create('widgets', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('status')->default('active');
        $table->softDeletes();
    });

    Schema::dropIfExists('job_batches');
    Schema::create('job_batches', function ($table) {
        $table->string('id')->primary();
        $table->string('name');
        $table->integer('total_jobs');
        $table->integer('pending_jobs');
        $table->integer('failed_jobs');
        $table->longText('failed_job_ids');
        $table->mediumText('options')->nullable();
        $table->integer('cancelled_at')->nullable();
        $table->integer('created_at');
        $table->integer('finished_at')->nullable();
    });

    Schema::dropIfExists('rocket_exports');
    Schema::create('rocket_exports', function ($table) {
        $table->id();
        $table->string('exporter');
        $table->string('file_name');
        $table->string('file_disk');
        $table->unsignedInteger('total_rows')->default(0);
        $table->unsignedInteger('successful_rows')->default(0);
        $table->string('batch_id')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
    });

    app(PanelManager::class)->register(
        Panel::make('test')
            ->path('test-admin')
            ->authMiddleware([])
            ->resources([WidgetResource::class])
    );
});

it('renders a CSV row from an exporter column', function () {
    $widget = Widget::query()->create(['name' => 'Rocket', 'status' => 'active']);

    $state = WidgetExporter::getColumns()[1]->getStateAsString($widget);

    expect($state)->toBe('Rocket');
});

it('serializes exporter header from column labels', function () {
    expect(WidgetExporter::getHeader())->toBe(['ID', 'Name', 'Status']);
});

it('writes a full CSV end-to-end via batched jobs (sync)', function () {
    for ($i = 1; $i <= 25; $i++) {
        Widget::query()->create(['name' => "Widget {$i}", 'status' => 'active']);
    }

    $export = Export::query()->create([
        'exporter' => WidgetExporter::class,
        'file_name' => 'widgets.csv',
        'file_disk' => 'local',
        'total_rows' => 0,
        'successful_rows' => 0,
    ]);

    // Run synchronously
    (new PrepareExportJob($export->getKey(), WidgetExporter::class))->handle();

    $export->refresh();
    expect($export->total_rows)->toBe(25)
        ->and($export->completed_at)->not->toBeNull()
        ->and($export->successful_rows)->toBe(25);

    $path = 'rocket-exports/'.$export->getKey().'/widgets.csv';
    expect(Storage::disk('local')->exists($path))->toBeTrue();

    $csv = Storage::disk('local')->get($path);
    $lines = array_values(array_filter(explode("\n", (string) $csv), fn ($l) => $l !== ''));

    expect($lines[0])->toBe('ID,Name,Status')
        ->and(count($lines))->toBe(26);
});

it('cleans up chunk files after concatenation', function () {
    Widget::query()->create(['name' => 'A', 'status' => 'active']);

    $export = Export::query()->create([
        'exporter' => WidgetExporter::class,
        'file_name' => 'widgets.csv',
        'file_disk' => 'local',
        'total_rows' => 0,
        'successful_rows' => 0,
    ]);

    (new PrepareExportJob($export->getKey(), WidgetExporter::class))->handle();

    $files = Storage::disk('local')->files('rocket-exports/'.$export->getKey());
    // Only the final file should remain.
    expect(collect($files)->filter(fn ($f) => str_contains($f, 'chunk_'))->count())->toBe(0);
});

it('handles an empty query by completing immediately', function () {
    $export = Export::query()->create([
        'exporter' => WidgetExporter::class,
        'file_name' => 'widgets.csv',
        'file_disk' => 'local',
        'total_rows' => 0,
        'successful_rows' => 0,
    ]);

    (new PrepareExportJob($export->getKey(), WidgetExporter::class))->handle();

    $export->refresh();
    expect($export->total_rows)->toBe(0)
        ->and($export->completed_at)->not->toBeNull();
});

it('dispatches PrepareExportJob from ExportBulkAction handle', function () {
    Bus::fake();

    $a = Widget::query()->create(['name' => 'A', 'status' => 'active']);
    $b = Widget::query()->create(['name' => 'B', 'status' => 'active']);

    $action = ExportBulkAction::make(WidgetExporter::class);
    $action->handle(collect([$a, $b]));

    Bus::assertDispatched(PrepareExportJob::class, function (PrepareExportJob $job) use ($a, $b) {
        return $job->exporter === WidgetExporter::class
            && $job->recordIds === [$a->id, $b->id];
    });

    expect(Export::query()->count())->toBe(1);
});

it('dispatches PrepareExportJob from ExportAction handle (header action)', function () {
    Bus::fake();

    Widget::query()->create(['name' => 'A', 'status' => 'active']);
    Widget::query()->create(['name' => 'B', 'status' => 'inactive']);

    $request = Request::create('/test-admin/widgets', 'POST');
    $request->setUserResolver(fn () => new GenericUser(['id' => 7]));

    $action = ExportAction::make(WidgetExporter::class);
    $action->handle($request, Widget::query());

    Bus::assertDispatched(PrepareExportJob::class);
    expect(Export::query()->first()->user_id)->toBe(7);
});

it('downloads a completed export via the panel endpoint', function () {
    $export = Export::query()->create([
        'exporter' => WidgetExporter::class,
        'file_name' => 'widgets.csv',
        'file_disk' => 'local',
        'total_rows' => 1,
        'successful_rows' => 1,
        'user_id' => 1,
        'completed_at' => now(),
    ]);

    Storage::disk('local')->put('rocket-exports/'.$export->id.'/widgets.csv', "ID,Name\n1,Hi");

    test()->withHeaders(['Accept' => 'application/json'])->get("/test-admin/exports/{$export->id}/download")
        ->assertOk();
});

it('forbids downloading another user\'s export', function () {
    $export = Export::query()->create([
        'exporter' => WidgetExporter::class,
        'file_name' => 'widgets.csv',
        'file_disk' => 'local',
        'total_rows' => 1,
        'successful_rows' => 1,
        'user_id' => 999,
        'completed_at' => now(),
    ]);

    Storage::disk('local')->put('rocket-exports/'.$export->id.'/widgets.csv', 'x');

    test()->withHeaders(['Accept' => 'application/json'])->get("/test-admin/exports/{$export->id}/download")
        ->assertForbidden();
});

it('404s on an incomplete export download', function () {
    $export = Export::query()->create([
        'exporter' => WidgetExporter::class,
        'file_name' => 'widgets.csv',
        'file_disk' => 'local',
        'total_rows' => 0,
        'successful_rows' => 0,
    ]);

    test()->withHeaders(['Accept' => 'application/json'])->get("/test-admin/exports/{$export->id}/download")
        ->assertNotFound();
});

it('exporter column formats money, prefix, suffix', function () {
    $widget = new Widget(['name' => 'P']);
    $widget->setAttribute('price', 12345); // cents

    $col = \MaherElGamil\Rocket\Exports\ExportColumn::make('price')->money('usd');
    expect($col->getStateAsString($widget))->toBe('123.45 USD');

    $col2 = \MaherElGamil\Rocket\Exports\ExportColumn::make('name')->prefix('>')->suffix('<');
    expect($col2->getStateAsString($widget))->toBe('>P<');
});
