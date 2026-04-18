import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import DataTable from '../components/data-table';
import PanelShell from '../components/panel-shell';
import { Button } from '../components/ui/button';

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    resource: {
        slug: string;
        label: string;
        pluralLabel: string;
        hasForm: boolean;
    };
    table: Parameters<typeof DataTable>[0]['schema'];
    records: Parameters<typeof DataTable>[0]['records'];
    pagination: Parameters<typeof DataTable>[0]['pagination'];
    filters: Parameters<typeof DataTable>[0]['filters'];
};

export default function ListRecords({
    panel,
    resource,
    table,
    records,
    pagination,
    filters,
}: Props) {
    const baseUrl = `/${panel.path}/${resource.slug}`;

    return (
        <PanelShell panel={panel} activeSlug={resource.slug}>
            <Head title={resource.pluralLabel} />
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{resource.pluralLabel}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Manage your {resource.pluralLabel.toLowerCase()} records.
                    </p>
                </div>
                {resource.hasForm && (
                    <Button asChild>
                        <Link href={`${baseUrl}/create`}>
                            <Plus className="mr-2 size-4" />
                            New {resource.label}
                        </Link>
                    </Button>
                )}
            </div>
            <DataTable
                schema={table}
                records={records}
                pagination={pagination}
                filters={filters}
                baseUrl={baseUrl}
                editable={resource.hasForm}
            />
        </PanelShell>
    );
}
