import { Head, Link } from '@inertiajs/react';
import ActivityFeedWidget from '../components/activity-feed-widget';
import ChartWidget from '../components/chart-widget';
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

type ChartWidgetType = {
    type: 'chart';
    chart_type: 'line' | 'bar' | 'area';
    title: string;
    color: string;
    data: { label: string; value: number }[];
};

type RecentRecordsWidget = {
    type: 'recent_records';
    title: string;
    columns: { name: string; label: string }[];
    rows: Record<string, unknown>[];
    resource_url: string | null;
};

type ActivityFeedWidgetType = {
    type: 'activity_feed';
    title: string;
    items: { title: string; time: string | null; icon: string }[];
};

type Widget = StatWidget | TableWidget | ChartWidgetType | RecentRecordsWidget | ActivityFeedWidgetType;

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    widgets: Widget[];
};

function WidgetCard({ title, span, children, footer }: { title?: string; span?: string; children: React.ReactNode; footer?: React.ReactNode }) {
    return (
        <Card className={`p-0 ${span ?? ''}`}>
            {title && (
                <div className="border-b px-6 py-4">
                    <h2 className="text-sm font-medium">{title}</h2>
                </div>
            )}
            <div className="p-6">{children}</div>
            {footer && <div className="border-t px-6 py-3">{footer}</div>}
        </Card>
    );
}

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

                    if (w.type === 'chart') {
                        return (
                            <WidgetCard key={i} title={w.title} span="md:col-span-2">
                                <ChartWidget chartType={w.chart_type} data={w.data} color={w.color} />
                            </WidgetCard>
                        );
                    }

                    if (w.type === 'recent_records') {
                        return (
                            <WidgetCard
                                key={i}
                                title={w.title}
                                span="md:col-span-2 lg:col-span-3"
                                footer={
                                    w.resource_url ? (
                                        <Link href={`/${w.resource_url}`} className="text-xs text-muted-foreground hover:text-foreground">
                                            View all →
                                        </Link>
                                    ) : undefined
                                }
                            >
                                <div className="-mx-6 -mt-6">
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
                                                    <TableCell colSpan={w.columns.length} className="text-center text-muted-foreground">
                                                        No records
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
                                </div>
                            </WidgetCard>
                        );
                    }

                    if (w.type === 'activity_feed') {
                        return (
                            <WidgetCard key={i} title={w.title}>
                                <ActivityFeedWidget items={w.items} />
                            </WidgetCard>
                        );
                    }

                    if (w.type === 'table') {
                        return (
                            <WidgetCard key={i} title={w.title} span="md:col-span-2 lg:col-span-3">
                                <div className="-mx-6 -mt-6">
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
                                                    <TableCell colSpan={w.columns.length} className="text-center text-muted-foreground">
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
                                </div>
                            </WidgetCard>
                        );
                    }

                    return null;
                })}
            </div>
        </PanelShell>
    );
}
