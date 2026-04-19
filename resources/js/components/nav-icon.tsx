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

export default function NavIcon({
    name,
    className,
}: {
    name: string | null | undefined;
    className?: string;
}) {
    if (!name) return null;
    const Icon = NAV_ICONS[name];
    if (!Icon) return null;
    return <Icon className={cn('size-4 shrink-0 opacity-80', className)} aria-hidden />;
}
