import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import DataTable from '../components/data-table';
import PanelShell from '../components/panel-shell';
import { Button } from '../components/ui/button';

type RowActionSchema = {
    name: string;
    label: string;
    requires_confirmation: boolean;
    destructive: boolean;
    icon: string | null;
    scope: string;
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
};

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
}: Props) {
    const baseUrl = `/${panel.path}/${resource.slug}`;
    const canCreate = resource.can?.create === true;

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
                {resource.hasForm && canCreate && (
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
