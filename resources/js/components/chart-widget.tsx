import { lazy, Suspense } from 'react';
import { Skeleton } from './ui/skeleton';

const ChartWidgetInner = lazy(() => import('./chart-widget-inner'));

type Props = {
    chartType: 'line' | 'bar' | 'area';
    data: { label: string; value: number }[];
    color: string;
};

export default function ChartWidget(props: Props) {
    return (
        <Suspense fallback={<Skeleton className="h-[220px] w-full" />}>
            <ChartWidgetInner {...props} />
        </Suspense>
    );
}
