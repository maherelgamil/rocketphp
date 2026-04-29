<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use MaherElGamil\Rocket\Imports\Importer;
use MaherElGamil\Rocket\Models\FailedImportRow;
use MaherElGamil\Rocket\Models\Import;
use MaherElGamil\Rocket\Panel\PanelManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ImportController extends Controller
{
    public function __construct(private readonly PanelManager $panels) {}

    /**
     * Return an example CSV for the given importer.
     */
    public function example(Request $request, string $importer): StreamedResponse
    {
        unset($request);

        /** @var class-string<Importer>|null $class */
        $class = $this->resolveImporter($importer);
        if ($class === null) {
            abort(404);
        }

        $csv = $class::getExampleCsv();

        return response()->streamDownload(
            fn () => print $csv,
            'import-example.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * Preview an uploaded CSV: returns the first N rows + a guessed mapping.
     */
    public function preview(Request $request, string $importer): JsonResponse
    {
        /** @var class-string<Importer>|null $class */
        $class = $this->resolveImporter($importer);
        if ($class === null) {
            abort(404);
        }

        $request->validate([
            'file' => ['required', 'file'],
        ]);

        $contents = file_get_contents($request->file('file')->getRealPath());
        $rows = [];
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, (string) $contents);
        rewind($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
            if (count($rows) > 11) {
                break;
            }
        }
        fclose($handle);

        $header = $rows[0] ?? [];
        $mapping = $class::guessMapping($header);

        $columns = array_map(static fn ($c) => [
            'name' => $c->getName(),
            'label' => $c->getLabel(),
            'required_mapping' => $c->isRequiredMapping(),
        ], $class::getColumns());

        return response()->json([
            'header' => $header,
            'preview' => array_slice($rows, 1, 10),
            'mapping' => $mapping,
            'columns' => $columns,
        ]);
    }

    public function show(Request $request, int $import): Response
    {
        /** @var Import|null $record */
        $record = Import::query()->find($import);
        if ($record === null) {
            abort(404);
        }

        $userId = $request->user()?->getAuthIdentifier();
        if ($record->user_id !== null && $record->user_id !== $userId) {
            abort(403);
        }

        return Inertia::render('rocket/import', [
            'import' => [
                'id' => $record->getKey(),
                'importer' => $record->importer,
                'file_name' => $record->file_name,
                'status' => $record->getStatus(),
                'progress' => $record->getProgress(),
                'total_rows' => $record->total_rows,
                'processed_rows' => $record->processed_rows,
                'successful_rows' => $record->successful_rows,
                'failed_rows' => $record->failed_rows,
                'completed_at' => $record->completed_at,
            ],
        ]);
    }

    public function status(Request $request, int $import): JsonResponse
    {
        /** @var Import|null $record */
        $record = Import::query()->find($import);
        if ($record === null) {
            abort(404);
        }

        $userId = $request->user()?->getAuthIdentifier();
        if ($record->user_id !== null && $record->user_id !== $userId) {
            abort(403);
        }

        return response()->json([
            'status' => $record->getStatus(),
            'progress' => $record->getProgress(),
            'total_rows' => $record->total_rows,
            'processed_rows' => $record->processed_rows,
            'successful_rows' => $record->successful_rows,
            'failed_rows' => $record->failed_rows,
            'completed_at' => $record->completed_at,
        ]);
    }

    public function downloadFailedRows(Request $request, int $import): StreamedResponse
    {
        /** @var Import|null $record */
        $record = Import::query()->find($import);
        if ($record === null) {
            abort(404);
        }

        $userId = $request->user()?->getAuthIdentifier();
        if ($record->user_id !== null && $record->user_id !== $userId) {
            abort(403);
        }

        return response()->streamDownload(function () use ($record): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['row_json', 'error']);

            FailedImportRow::query()
                ->where('import_id', $record->getKey())
                ->orderBy('id')
                ->chunk(500, function ($rows) use ($out): void {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            (string) json_encode($row->data, JSON_UNESCAPED_UNICODE),
                            (string) $row->validation_error,
                        ]);
                    }
                });

            fclose($out);
        }, 'failed-rows-'.$record->getKey().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return class-string<Importer>|null
     */
    private function resolveImporter(string $encoded): ?string
    {
        $class = base64_decode($encoded, true);
        if ($class === false || ! is_string($class) || ! class_exists($class)) {
            return null;
        }

        if (! is_subclass_of($class, Importer::class)) {
            return null;
        }

        /** @var class-string<Importer> $class */
        return $class;
    }
}
