import { Head, Link, router } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import PanelShell from '../components/panel-shell';
import { Badge } from '../components/ui/badge';
import { Button } from '../components/ui/button';
import { Card } from '../components/ui/card';

type Notification = {
    id: string;
    title: string;
    body: string | null;
    icon: string;
    url: string | null;
    read_at: string | null;
    created_at: string | null;
};

type Pagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    notifications: { data: Notification[] };
    pagination: Pagination;
};

function formatDate(iso: string | null): string {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function markAllRead(markAllUrl: string) {
    router.post(
        markAllUrl,
        {},
        {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['notifications'] }),
        },
    );
}

export default function Notifications({ panel, notifications, pagination }: Props) {
    const markAllUrl = panel.notifications?.urls.mark_all_read;

    return (
        <PanelShell panel={panel} activeSlug="__notifications__">
            <Head title="Notifications" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Notifications</h1>
                    <p className="text-sm text-muted-foreground">{pagination.total} total</p>
                </div>
                {markAllUrl && (
                    <Button variant="outline" size="sm" onClick={() => markAllRead(markAllUrl)}>
                        Mark all read
                    </Button>
                )}
            </div>

            <Card className="divide-y p-0">
                {notifications.data.length === 0 && (
                    <div className="flex flex-col items-center justify-center gap-3 py-16 text-muted-foreground">
                        <Bell className="size-8 opacity-40" />
                        <span className="text-sm">No notifications yet</span>
                    </div>
                )}

                {notifications.data.map((n) => (
                    <div key={n.id} className={`flex gap-4 px-6 py-4 ${n.read_at ? 'opacity-60' : ''}`}>
                        <div className="mt-1 shrink-0">
                            <Bell className="size-4 text-muted-foreground" />
                        </div>
                        <div className="min-w-0 flex-1">
                            {n.url ? (
                                <Link href={n.url} className="font-medium hover:underline">
                                    {n.title}
                                </Link>
                            ) : (
                                <p className="font-medium">{n.title}</p>
                            )}
                            {n.body && <p className="mt-0.5 text-sm text-muted-foreground">{n.body}</p>}
                            <p className="mt-1 text-xs text-muted-foreground">{formatDate(n.created_at)}</p>
                        </div>
                        <div className="shrink-0">
                            {n.read_at ? (
                                <Badge variant="secondary" className="text-xs">
                                    Read
                                </Badge>
                            ) : (
                                <Badge className="text-xs">New</Badge>
                            )}
                        </div>
                    </div>
                ))}
            </Card>

            {pagination.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        {pagination.from ?? 0}–{pagination.to ?? 0} of {pagination.total}
                    </span>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={pagination.current_page <= 1}
                            onClick={() => router.get(window.location.pathname, { page: pagination.current_page - 1 }, { preserveScroll: true })}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={pagination.current_page >= pagination.last_page}
                            onClick={() => router.get(window.location.pathname, { page: pagination.current_page + 1 }, { preserveScroll: true })}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}
        </PanelShell>
    );
}
