import { Head } from '@inertiajs/react';
import PanelShell from '../components/panel-shell';
import { renderWidget } from '../components/widget-renderer';
import { WidgetBlock, DashboardWidget } from '../lib/types';
import { gridClass } from '../lib/grid';
import { create__ } from '../lib/i18n';

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    content: WidgetBlock[];
};

export default function Dashboard({ panel, content }: Props) {
    const __ = create__(panel.translations ?? {});
    const widgets: DashboardWidget[] = content.map((b) => b.widget);

    return (
        <PanelShell panel={panel} activeSlug="__dashboard__">
            <Head title={__('Dashboard')} />
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight">{__('Dashboard')}</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    {__('Overview for :brand', { brand: panel.brand })}
                </p>
            </div>
            <div className={gridClass(panel.dashboard_columns)}>
                {widgets.map((w, i) => renderWidget(w, i))}
            </div>
        </PanelShell>
    );
}