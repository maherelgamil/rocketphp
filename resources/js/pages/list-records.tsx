import { Head, Link, router } from '@inertiajs/react';
import { Download, Plus, Upload } from 'lucide-react';
import { DynamicIcon } from 'lucide-react/dynamic';
import { useRef, useState } from 'react';
import DataTable from '../components/data-table';
import PanelShell from '../components/panel-shell';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '../components/ui/sheet';
import { create__ } from '../lib/i18n';

type RowActionSchema = {
    name: string;
    label: string;
    requires_confirmation: boolean;
    destructive: boolean;
    icon: string | null;
    scope: string;
};

type HeaderActionSchema = {
    name: string;
    label: string;
    icon: string | null;
    scope: 'header';
    importer_key?: string;
};

type ImportColumn = {
    name: string;
    label: string;
    required_mapping: boolean;
};

type PreviewResponse = {
    header: string[];
    preview: string[][];
    mapping: Record<string, number | null>;
    columns: ImportColumn[];
};

type TableFilterSchema = Record<string, unknown> & { type: string; name: string };

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    resource: {
        slug: string;
        label: string;
        pluralLabel: string;
        hasForm: boolean;
        can?: { create?: boolean };
    };
    table: Parameters<typeof DataTable>[0]['schema'];
    records: Parameters<typeof DataTable>[0]['records'];
    pagination: Parameters<typeof DataTable>[0]['pagination'];
    filters: Parameters<typeof DataTable>[0]['filters'];
    query: Record<string, unknown>;
    row_actions?: RowActionSchema[];
    row_actions_overflow_after?: number;
    bulk_actions?: RowActionSchema[];
    table_filters?: TableFilterSchema[];
    per_page_options?: number[];
    header_actions?: HeaderActionSchema[];
};

function ExportButton({ label, icon, url }: { label: string; icon: string | null; url: string }) {
    const [loading, setLoading] = useState(false);

    function handleClick() {
        setLoading(true);
        router.post(url, {}, { onFinish: () => setLoading(false) });
    }

    return (
        <Button variant="outline" onClick={handleClick} disabled={loading}>
            {icon ? <DynamicIcon name={icon as never} className="me-2 size-4" /> : <Download className="me-2 size-4" />}
            {label}
        </Button>
    );
}

function ImportButton({
    label,
    icon,
    importerKey,
    actionUrl,
    panelPath,
}: {
    label: string;
    icon: string | null;
    importerKey: string;
    actionUrl: string;
    panelPath: string;
}) {
    const [open, setOpen] = useState(false);
    const [step, setStep] = useState<'upload' | 'mapping'>('upload');
    const [loading, setLoading] = useState(false);
    const [preview, setPreview] = useState<PreviewResponse | null>(null);
    const [mapping, setMapping] = useState<Record<string, string>>({});
    const [file, setFile] = useState<File | null>(null);
    const fileRef = useRef<HTMLInputElement>(null);

    function reset() {
        setStep('upload');
        setPreview(null);
        setMapping({});
        setFile(null);
        if (fileRef.current) fileRef.current.value = '';
    }

    async function handleUpload() {
        if (!file) return;
        setLoading(true);

        const form = new FormData();
        form.append('file', file);
        form.append('_token', (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '');

        try {
            const res = await fetch(`/${panelPath}/importers/${importerKey}/preview`, {
                method: 'POST',
                body: form,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data: PreviewResponse = await res.json();
            setPreview(data);
            const initial: Record<string, string> = {};
            for (const [col, idx] of Object.entries(data.mapping)) {
                initial[col] = idx !== null ? String(idx) : '';
            }
            setMapping(initial);
            setStep('mapping');
        } finally {
            setLoading(false);
        }
    }

    function handleImport() {
        if (!file || !preview) return;
        setLoading(true);

        const finalMapping: Record<string, number | null> = {};
        for (const [col, val] of Object.entries(mapping)) {
            finalMapping[col] = val !== '' ? Number(val) : null;
        }

        const form = new FormData();
        form.append('file', file);
        form.append('mapping', JSON.stringify(finalMapping));

        router.post(actionUrl, form as never, {
            onFinish: () => {
                setLoading(false);
                setOpen(false);
                reset();
            },
        });
    }

    return (
        <Sheet
            open={open}
            onOpenChange={(v) => {
                setOpen(v);
                if (!v) reset();
            }}
        >
            <SheetTrigger asChild>
                <Button variant="outline">
                    {icon ? <DynamicIcon name={icon as never} className="me-2 size-4" /> : <Upload className="me-2 size-4" />}
                    {label}
                </Button>
            </SheetTrigger>
            <SheetContent side="right" className="w-full sm:max-w-lg overflow-y-auto">
                <SheetHeader>
                    <SheetTitle>{label}</SheetTitle>
                    <SheetDescription>
                        {step === 'upload' ? 'Upload a CSV file to import records.' : 'Map CSV columns to the import fields.'}
                    </SheetDescription>
                </SheetHeader>

                <div className="flex flex-col gap-4 p-4">
                    {step === 'upload' && (
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="import-file">CSV File</Label>
                            <Input
                                id="import-file"
                                ref={fileRef}
                                type="file"
                                accept=".csv,text/csv"
                                onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                            />
                        </div>
                    )}

                    {step === 'mapping' && preview && (
                        <div className="flex flex-col gap-4">
                            {preview.columns.map((col) => (
                                <div key={col.name} className="flex flex-col gap-1">
                                    <Label>
                                        {col.label}
                                        {col.required_mapping && <span className="ms-1 text-destructive">*</span>}
                                    </Label>
                                    <Select
                                        value={mapping[col.name] ?? ''}
                                        onValueChange={(val) => setMapping((m) => ({ ...m, [col.name]: val }))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="— skip —" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">— skip —</SelectItem>
                                            {preview.header.map((h, i) => (
                                                <SelectItem key={i} value={String(i)}>
                                                    {h}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <SheetFooter className="flex-row justify-end gap-2">
                    {step === 'mapping' && (
                        <Button variant="ghost" onClick={() => setStep('upload')} disabled={loading}>
                            Back
                        </Button>
                    )}
                    {step === 'upload' && (
                        <Button onClick={handleUpload} disabled={!file || loading}>
                            {loading ? 'Reading…' : 'Next'}
                        </Button>
                    )}
                    {step === 'mapping' && (
                        <Button onClick={handleImport} disabled={loading}>
                            {loading ? 'Importing…' : 'Import'}
                        </Button>
                    )}
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}

export default function ListRecords({
    panel,
    resource,
    table,
    records,
    pagination,
    filters,
    query,
    row_actions = [],
    row_actions_overflow_after = 3,
    bulk_actions = [],
    table_filters = [],
    per_page_options = [10, 25, 50, 100],
    header_actions = [],
}: Props) {
    const __ = create__(panel.translations ?? {});
    const baseUrl = `/${panel.path}/${resource.slug}`;
    const canCreate = resource.can?.create === true;

    const exportActions = header_actions.filter((a) => a.name === 'export' || !a.importer_key);
    const importActions = header_actions.filter((a) => a.importer_key);

    return (
        <PanelShell panel={panel} activeSlug={resource.slug}>
            <Head title={resource.pluralLabel} />
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{resource.pluralLabel}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {__('Manage your :resource records.', { resource: resource.pluralLabel.toLowerCase() })}
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    {importActions.map((action) => (
                        <ImportButton
                            key={action.name}
                            label={action.label}
                            icon={action.icon}
                            importerKey={action.importer_key!}
                            actionUrl={`${baseUrl}/header-actions/${action.name}`}
                            panelPath={panel.path}
                        />
                    ))}
                    {exportActions.map((action) => (
                        <ExportButton
                            key={action.name}
                            label={action.label}
                            icon={action.icon}
                            url={`${baseUrl}/header-actions/${action.name}`}
                        />
                    ))}
                    {resource.hasForm && canCreate && (
                        <Button asChild>
                            <Link href={`${baseUrl}/create`}>
                                <Plus className="me-2 size-4" />
                                {__('New :resource', { resource: resource.label })}
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
            <DataTable
                schema={table}
                records={records}
                pagination={pagination}
                filters={filters}
                query={query}
                baseUrl={baseUrl}
                editable={resource.hasForm}
                rowActions={row_actions}
                rowActionsOverflowAfter={row_actions_overflow_after}
                bulkActions={bulk_actions}
                tableFilters={table_filters as Parameters<typeof DataTable>[0]['tableFilters']}
                perPageOptions={per_page_options}
            />
        </PanelShell>
    );
}
