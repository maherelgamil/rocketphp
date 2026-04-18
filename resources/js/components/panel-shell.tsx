import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Box,
    FileText,
    FolderTree,
    LayoutDashboard,
    ListTodo,
    Package,
    Settings,
    Tags,
    Users,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '../lib/utils';

const NAV_ICONS: Record<string, LucideIcon> = {
    'layout-dashboard': LayoutDashboard,
    package: Package,
    box: Box,
    users: Users,
    settings: Settings,
    'file-text': FileText,
    'folder-tree': FolderTree,
    tags: Tags,
    'list-todo': ListTodo,
};

function NavIcon({ name }: { name: string | null | undefined }) {
    if (!name) {
        return null;
    }
    const Icon = NAV_ICONS[name];
    if (!Icon) {
        return null;
    }
    return <Icon className="mr-2 size-4 shrink-0 opacity-80" aria-hidden />;
}

type NavItem = {
    label: string;
    slug: string;
    url: string;
    group?: string | null;
    icon?: string | null;
};

type PanelProps = {
    id: string;
    brand: string;
    path: string;
    navigation: NavItem[];
};

type Props = {
    panel: PanelProps;
    activeSlug?: string;
    children: ReactNode;
};

export default function PanelShell({ panel, activeSlug, children }: Props) {
    const groups = panel.navigation.reduce<Record<string, NavItem[]>>((acc, item) => {
        const key = item.group ?? '';
        (acc[key] ||= []).push(item);
        return acc;
    }, {});

    return (
        <div className="flex min-h-screen bg-muted/30">
            <aside className="flex w-64 shrink-0 flex-col border-r bg-card">
                <div className="flex h-16 items-center border-b px-6">
                    <span className="text-lg font-semibold tracking-tight">{panel.brand}</span>
                </div>
                <nav className="flex-1 overflow-y-auto px-3 py-4">
                    {Object.entries(groups).map(([group, items]) => (
                        <div key={group || 'default'} className="mb-6 last:mb-0">
                            {group && (
                                <div className="mb-2 px-3 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                    {group}
                                </div>
                            )}
                            <ul className="space-y-1">
                                {items.map((item) => (
                                    <li key={item.slug}>
                                        <Link
                                            href={item.url}
                                            className={cn(
                                                'flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                                'hover:bg-accent hover:text-accent-foreground',
                                                activeSlug === item.slug
                                                    ? 'bg-accent text-accent-foreground'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            <NavIcon name={item.icon} />
                                            {item.label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </nav>
            </aside>
            <main className="flex-1 overflow-x-auto">
                <div className="mx-auto max-w-7xl p-8">{children}</div>
            </main>
        </div>
    );
}
