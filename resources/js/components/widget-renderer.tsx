import { Link } from '@inertiajs/react';
import { Card } from './ui/card';
import ChartWidget from './chart-widget';
import ActivityFeedWidget from './activity-feed-widget';
import { WidgetCard } from './widget-card';
import { TableBlockRenderer } from './table-block-renderer';
import { DashboardWidget } from '../lib/types';
import { colSpanClass } from '../lib/grid';

export function renderWidget(w: DashboardWidget, key: number): React.ReactNode {
    if (w.type === 'stat') {
        return (
            <Card key={key} className={colSpanClass(w.column_span)}>
                <p className="text-sm font-medium text-muted-foreground">{w.label}</p>
                <p className="mt-2 text-3xl font-semibold tabular-nums">{w.value}</p>
            </Card>
        );
    }

    if (w.type === 'chart') {
        return (
            <WidgetCard key={key} title={w.title} columnSpan={w.column_span}>
                <ChartWidget chartType={w.chart_type} data={w.data} color={w.color} />
            </WidgetCard>
        );
    }

    if (w.type === 'recent_records') {
        return (
            <WidgetCard
                key={key}
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
                <TableBlockRenderer columns={w.columns} rows={w.rows} emptyMessage="No records" />
            </WidgetCard>
        );
    }

    if (w.type === 'activity_feed') {
        return (
            <WidgetCard key={key} title={w.title} columnSpan={w.column_span}>
                <ActivityFeedWidget items={w.items} />
            </WidgetCard>
        );
    }

    if (w.type === 'table') {
        return (
            <WidgetCard key={key} title={w.title} columnSpan={w.column_span}>
                <TableBlockRenderer columns={w.columns} rows={w.rows} emptyMessage="No rows" />
            </WidgetCard>
        );
    }

    return null;
}
