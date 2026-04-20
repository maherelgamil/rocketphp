import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useEffect, useState } from 'react';
import NavIcon from './nav-icon';
import { Button } from './ui/button';
import { Popover, PopoverContent, PopoverTrigger } from './ui/popover';

type Notification = {
    id: string;
    title: string;
    body: string | null;
    icon: string;
    url: string | null;
    read_at: string | null;
    created_at: string | null;
};

type NotificationUrls = {
    index: string;
    recent: string;
    mark_all_read: string;
};

type SharedNotifications = {
    unread_count?: number;
};

type Props = {
    urls: NotificationUrls;
    __?: (key: string, replacements?: Record<string, string | number>) => string;
};

function timeAgo(iso: string | null): string {
    if (!iso) return '';
    const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}

export default function NotificationBell({ urls, __ = (key) => key }: Props) {
    const { notifications: shared } = usePage<{ notifications: SharedNotifications }>().props;
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState<Notification[]>([]);
    const [loading, setLoading] = useState(false);
    const [unreadCount, setUnreadCount] = useState(shared?.unread_count ?? 0);

    useEffect(() => {
        setUnreadCount(shared?.unread_count ?? 0);
    }, [shared?.unread_count]);

    useEffect(() => {
        if (!open) return;
        setLoading(true);
        fetch(urls.recent, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.json())
            .then((data: { notifications: Notification[] }) => setItems(data.notifications))
            .finally(() => setLoading(false));
    }, [open, urls.recent]);

    function markRead(id: string) {
        const markUrl = urls.recent.replace('/recent', `/${id}/read`);
        fetch(markUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
            },
        })
            .then((r) => r.json())
            .then((data: { unread_count: number }) => {
                setUnreadCount(data.unread_count);
                setItems((prev) => prev.map((n) => (n.id === id ? { ...n, read_at: new Date().toISOString() } : n)));
            });
    }

    function markAllRead() {
        fetch(urls.mark_all_read, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
            },
        }).then(() => {
            setUnreadCount(0);
            setItems((prev) => prev.map((n) => ({ ...n, read_at: n.read_at ?? new Date().toISOString() })));
        });
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button variant="ghost" size="sm" className="relative size-9 p-0" aria-label={__('Notifications')}>
                    <Bell className="size-4" />
                    {unreadCount > 0 && (
                        <span className="absolute -end-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-primary text-[10px] font-semibold text-primary-foreground">
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </span>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between border-b px-4 py-3">
                    <span className="text-sm font-semibold">{__('Notifications')}</span>
                    {unreadCount > 0 && (
                        <button
                            type="button"
                            onClick={markAllRead}
                            className="text-xs text-muted-foreground hover:text-foreground"
                        >
                            {__('Mark all read')}
                        </button>
                    )}
                </div>

                <div className="max-h-80 overflow-y-auto">
                    {loading && (
                        <div className="flex items-center justify-center py-8 text-sm text-muted-foreground">
                            {__('Loading…')}
                        </div>
                    )}

                    {!loading && items.length === 0 && (
                        <div className="flex flex-col items-center justify-center gap-2 py-8 text-sm text-muted-foreground">
                            <Bell className="size-6 opacity-40" />
                            <span>{__('No unread notifications')}</span>
                        </div>
                    )}

                    {!loading &&
                        items.map((item) => (
                            <div
                                key={item.id}
                                className={`flex gap-3 border-b px-4 py-3 last:border-0 ${item.read_at ? 'opacity-60' : ''}`}
                            >
                                <div className="mt-0.5 shrink-0 text-muted-foreground">
                                    <NavIcon name={item.icon} className="size-4" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    {item.url ? (
                                        <Link
                                            href={item.url}
                                            className="line-clamp-1 text-sm font-medium hover:underline"
                                            onClick={() => setOpen(false)}
                                        >
                                            {item.title}
                                        </Link>
                                    ) : (
                                        <p className="line-clamp-1 text-sm font-medium">{item.title}</p>
                                    )}
                                    {item.body && (
                                        <p className="line-clamp-2 text-xs text-muted-foreground">{item.body}</p>
                                    )}
                                    <p className="mt-1 text-xs text-muted-foreground">{timeAgo(item.created_at)}</p>
                                </div>
                                {!item.read_at && (
                                    <button
                                        type="button"
                                        onClick={() => markRead(item.id)}
                                        className="mt-1 shrink-0 text-xs text-muted-foreground hover:text-foreground"
                                        aria-label={__('Mark as read')}
                                    >
                                        ✓
                                    </button>
                                )}
                            </div>
                        ))}
                </div>

                <div className="border-t px-4 py-2">
                    <Link
                        href={urls.index}
                        className="text-xs text-muted-foreground hover:text-foreground"
                        onClick={() => setOpen(false)}
                    >
                        {__('View all notifications →')}
                    </Link>
                </div>
            </PopoverContent>
        </Popover>
    );
}
