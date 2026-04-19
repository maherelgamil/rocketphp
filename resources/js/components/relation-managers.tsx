import DataTable from './data-table';

type Manager = {
    name: string;
    title: string;
    prefix: string;
    page_key: string;
    table: Parameters<typeof DataTable>[0]['schema'];
    row_actions?: Parameters<typeof DataTable>[0]['rowActions'];
    row_actions_overflow_after?: number;
    table_filters?: Parameters<typeof DataTable>[0]['tableFilters'];
    records: Parameters<typeof DataTable>[0]['records'];
    pagination: Parameters<typeof DataTable>[0]['pagination'];
    per_page_options?: number[];
    filters: Parameters<typeof DataTable>[0]['filters'];
};

type Props = {
    managers: Record<string, Manager>;
    query: Record<string, unknown>;
    baseUrl: string;
};

export default function RelationManagers({ managers, query, baseUrl }: Props) {
    const entries = Object.values(managers);
    if (entries.length === 0) return null;

    return (
        <div className="mt-8 space-y-8">
            {entries.map((m) => (
                <section key={m.name} className="space-y-3">
                    <header>
                        <h2 className="text-lg font-semibold tracking-tight">{m.title}</h2>
                    </header>
                    <DataTable
                        schema={m.table}
                        records={m.records}
                        pagination={m.pagination}
                        filters={m.filters}
                        query={query}
                        baseUrl={baseUrl}
                        tableFilters={m.table_filters}
                        perPageOptions={m.per_page_options}
                        paramPrefix={m.prefix}
                    />
                </section>
            ))}
        </div>
    );
}
