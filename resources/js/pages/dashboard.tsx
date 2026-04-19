import { Head, Link } from '@inertiajs/react';
import ActivityFeedWidget from '../components/activity-feed-widget';
import ChartWidget from '../components/chart-widget';
import PanelShell from '../components/panel-shell';
import { colSpanClass, gridClass } from '../lib/grid';
import { cn } from '../lib/utils';
import { Card } from '../components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '../components/ui/table';

type StatWidget = { type: 'stat'; label: string; value: string | number; column_span: number | string };

type TableWidget = {
    type: 'table';
    title: string;
    column_span: number | string;
    columns: { name: string; label: string }[];
    rows: Record<string, unknown>[];
};

type ChartWidgetType = {
    type: 'chart';
    chart_type: 'line' | 'bar' | 'area';
    title: string;
    color: string;
    column_span: number | string;
    data: { label: string; value: number }[];
};

type RecentRecordsWidget = {
    type: 'recent_records';
    title: string;
    column_span: number | string;
    columns: { name: string; label: string }[];
    rows: Record<string, unknown>[];
    resource_url: string | null;
};

type ActivityFeedWidgetType = {
    type: 'activity_feed';
    title: string;
    column_span: number | string;
    items: { title: string; time: string | null; icon: string }[];
};

type Widget = StatWidget | TableWidget | ChartWidgetType | RecentRecordsWidget | ActivityFeedWidgetType;

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    widgets: Widget[];
};

function WidgetCard({
    title,
    columnSpan,
    children,
    footer,
}: {
    title?: string;
    columnSpan?: number | string;
    children: React.ReactNode;
    footer?: React.ReactNode;
}) {
    return (
        <Card className={cn('p-0', colSpanClass(columnSpan ?? 1))}>
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
            <div className={cn('grid gap-6', gridClass(panel.dashboard_columns))}>
                {widgets.map((w, i) => {
                    if (w.type === 'stat') {
                        return (
                            <Card key={i} className={cn('p-6', colSpanClass(w.column_span))}>
                                <p className="text-sm font-medium text-muted-foreground">{w.label}</p>
                                <p className="mt-2 text-3xl font-semibold tabular-nums">{w.value}</p>
                            </Card>
                        );
                    }

                    if (w.type === 'chart') {
                        return (
                            <WidgetCard key={i} title={w.title} columnSpan={w.column_span}>
                                <ChartWidget chartType={w.chart_type} data={w.data} color={w.color} />
                            </WidgetCard>
                        );
                    }

                    if (w.type === 'recent_records') {
                        return (
                            <WidgetCard
                                key={i}
                                title={w.title}
                                columnSpan={w.column_span}
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
                            <WidgetCard key={i} title={w.title} columnSpan={w.column_span}>
                                <ActivityFeedWidget items={w.items} />
                            </WidgetCard>
                        );
                    }

                    if (w.type === 'table') {
                        return (
                            <WidgetCard key={i} title={w.title} columnSpan={w.column_span}>
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
