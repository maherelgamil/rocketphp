import type { Translator } from '../lib/i18n';
import NavIcon from './nav-icon';

type FeedItem = {
    title: string;
    time: string | null;
    icon: string;
};

type Props = {
    items: FeedItem[];
    __?: Translator;
};

function timeAgo(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return iso;
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}

export default function ActivityFeedWidget({ items, __ = (key) => key }: Props) {
    if (items.length === 0) {
        return (
            <p className="py-6 text-center text-sm text-muted-foreground">{__('No recent activity')}</p>
        );
    }

    return (
        <div className="space-y-0">
            {items.map((item, i) => (
                <div key={i} className="flex gap-3">
                    <div className="flex flex-col items-center">
                        <div className="flex size-7 shrink-0 items-center justify-center rounded-full bg-muted">
                            <NavIcon name={item.icon} className="size-3.5 text-muted-foreground" />
                        </div>
                        {i < items.length - 1 && <div className="w-px flex-1 bg-border" />}
                    </div>
                    <div className="pb-4 pt-0.5">
                        <p className="text-sm">{item.title}</p>
                        {item.time && (
                            <p className="text-xs text-muted-foreground">{timeAgo(item.time)}</p>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}
