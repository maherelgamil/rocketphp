import { Card } from './ui/card';
import { colSpanClass } from '../lib/grid';
import { cn } from '../lib/utils';

export function WidgetCard({
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
