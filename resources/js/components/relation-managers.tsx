import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import DataTable from './data-table';
import { cn } from '../lib/utils';
import { Card } from './ui/card';

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

type Layout = 'tabs' | 'stacked';

type Props = {
    managers: Record<string, Manager>;
    query: Record<string, unknown>;
    layout?: Layout;
};

export default function RelationManagers({ managers, query, layout = 'tabs' }: Props) {
    const entries = Object.values(managers);
    if (entries.length === 0) return null;

    const pageUrl = usePage().url.split('?')[0] ?? '';

    const renderTable = (m: Manager) => (
        <DataTable
            schema={m.table}
            records={m.records}
            pagination={m.pagination}
            filters={m.filters}
            query={query}
            baseUrl={pageUrl}
            tableFilters={m.table_filters}
            perPageOptions={m.per_page_options}
            paramPrefix={m.prefix}
        />
    );

    if (layout === 'stacked') {
        return (
            <div className="mt-8 space-y-8 border-t pt-8">
                {entries.map((m) => (
                    <section key={m.name} className="space-y-3">
                        <header>
                            <h2 className="text-lg font-semibold tracking-tight">{m.title}</h2>
                        </header>
                        {renderTable(m)}
                    </section>
                ))}
            </div>
        );
    }

    return <RelationManagerTabs entries={entries} renderTable={renderTable} />;
}

function readHash(): string | null {
    if (typeof window === 'undefined') return null;
    const match = window.location.hash.match(/rm=([^&]+)/);
    return match ? decodeURIComponent(match[1]) : null;
}

function RelationManagerTabs({
    entries,
    renderTable,
}: {
    entries: Manager[];
    renderTable: (m: Manager) => React.ReactNode;
}) {
    const initial = (() => {
        const fromHash = readHash();
        return fromHash && entries.some((e) => e.name === fromHash) ? fromHash : entries[0].name;
    })();
    const [active, setActive] = useState(initial);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        url.hash = `rm=${active}`;
        window.history.replaceState(null, '', url.toString());
    }, [active]);

    const current = entries.find((e) => e.name === active) ?? entries[0];

    return (
        <div className="mt-8 border-t pt-8">
            <Card className="overflow-hidden p-0">
                <div role="tablist" className="flex gap-1 border-b px-4 pt-2">
                    {entries.map((m) => {
                        const isActive = m.name === active;
                        return (
                            <button
                                key={m.name}
                                type="button"
                                role="tab"
                                aria-selected={isActive}
                                onClick={() => setActive(m.name)}
                                className={cn(
                                    'inline-flex items-center gap-1.5 border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'border-primary text-foreground'
                                        : 'border-transparent text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {m.title}
                                <span className="rounded-full bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
                                    {m.pagination.total}
                                </span>
                            </button>
                        );
                    })}
                </div>
                <div className="p-4">{renderTable(current)}</div>
            </Card>
        </div>
    );
}
