import { Head } from '@inertiajs/react';
import PanelShell from '../components/panel-shell';
import { Card } from '../components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '../components/ui/table';

type StatWidget = { type: 'stat'; label: string; value: string | number };
type TableWidget = {
    type: 'table';
    title: string;
    columns: { name: string; label: string }[];
    rows: Record<string, unknown>[];
};

type Widget = StatWidget | TableWidget;

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    widgets: Widget[];
};

export default function Dashboard({ panel, widgets }: Props) {
    return (
        <PanelShell panel={panel} activeSlug="__dashboard__">
            <Head title="Dashboard" />
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
                <p className="mt-1 text-sm text-muted-foreground">Overview for {panel.brand}</p>
            </div>
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {widgets.map((w, i) => {
                    if (w.type === 'stat') {
                        return (
                            <Card key={i} className="p-6">
                                <p className="text-sm font-medium text-muted-foreground">{w.label}</p>
                                <p className="mt-2 text-3xl font-semibold tabular-nums">{w.value}</p>
                            </Card>
                        );
                    }
                    if (w.type === 'table') {
                        return (
                            <Card key={i} className="p-0 md:col-span-2 lg:col-span-3">
                                <div className="border-b px-6 py-4">
                                    <h2 className="text-lg font-medium">{w.title}</h2>
                                </div>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            {w.columns.map((c) => (
                                                <TableHead key={c.name}>{c.label}</TableHead>
                                            ))}
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {w.rows.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={w.columns.length}
                                                    className="text-center text-muted-foreground"
                                                >
                                                    No rows
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            w.rows.map((row, ri) => (
                                                <TableRow key={ri}>
                                                    {w.columns.map((c) => (
                                                        <TableCell key={c.name}>
                                                            {String(row[c.name] ?? '—')}
                                                        </TableCell>
                                                    ))}
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </Card>
                        );
                    }
                    return null;
                })}
            </div>
        </PanelShell>
    );
}
