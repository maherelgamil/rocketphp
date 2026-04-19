import { Link } from '@inertiajs/react';
import { Menu, PanelLeftClose, PanelLeftOpen, X } from 'lucide-react';
import React, { useEffect, useState, type ReactNode } from 'react';
import { useFlashToast } from '../hooks/use-flash-toast';
import { cn } from '../lib/utils';
import GlobalSearchDialog from './global-search-dialog';
import NavIcon from './nav-icon';
import { Button } from './ui/button';
import { Toaster } from './ui/sonner';

type NavItem = {
    label: string;
    slug: string;
    url: string;
    group?: string | null;
    icon?: string | null;
};

type GlobalSearch = {
    enabled: boolean;
    placeholder: string;
    url: string;
};

type PanelTheme = {
    primary?: string;
    accent?: string;
    radius?: string;
    density?: 'compact' | 'default' | 'comfortable';
    font?: string;
};

type PanelProps = {
    id: string;
    brand: string;
    path: string;
    navigation: NavItem[];
    global_search: GlobalSearch;
    theme?: PanelTheme;
};

type Props = {
    panel: PanelProps;
    activeSlug?: string;
    children: ReactNode;
};

const COLLAPSE_KEY = 'rocket:sidebar-collapsed';

const DENSITY_VARS: Record<string, Record<string, string>> = {
    compact: { '--rocket-gap': '0.5rem', '--rocket-input-height': '2rem', '--rocket-font-size': '0.8125rem' },
    default: { '--rocket-gap': '0.75rem', '--rocket-input-height': '2.5rem', '--rocket-font-size': '0.875rem' },
    comfortable: { '--rocket-gap': '1rem', '--rocket-input-height': '3rem', '--rocket-font-size': '1rem' },
};

function buildThemeVars(theme: PanelTheme | undefined): React.CSSProperties {
    if (!theme) return {};
    const vars: Record<string, string> = {};
    if (theme.primary) vars['--primary'] = theme.primary;
    if (theme.accent) vars['--accent'] = theme.accent;
    if (theme.radius) vars['--radius'] = theme.radius;
    if (theme.font) vars['--font-sans'] = `"${theme.font}", sans-serif`;
    Object.assign(vars, DENSITY_VARS[theme.density ?? 'default'] ?? DENSITY_VARS.default);
    return vars as unknown as React.CSSProperties;
}

export default function PanelShell({ panel, activeSlug, children }: Props) {
    useFlashToast();

    const [collapsed, setCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        setCollapsed(window.localStorage.getItem(COLLAPSE_KEY) === '1');
    }, []);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        window.localStorage.setItem(COLLAPSE_KEY, collapsed ? '1' : '0');
    }, [collapsed]);

    useEffect(() => {
        setMobileOpen(false);
    }, [activeSlug]);

    const groups = panel.navigation.reduce<Record<string, NavItem[]>>((acc, item) => {
        const key = item.group ?? '';
        (acc[key] ||= []).push(item);
        return acc;
    }, {});

    const renderNav = (compact: boolean) => (
        <>
            <div
                className={cn(
                    'flex h-16 items-center border-b',
                    compact ? 'justify-center px-2' : 'px-6',
                )}
            >
                {!compact && (
                    <span className="text-lg font-semibold tracking-tight">{panel.brand}</span>
                )}
                {compact && (
                    <span className="text-lg font-semibold tracking-tight">
                        {panel.brand.slice(0, 1)}
                    </span>
                )}
            </div>
            <nav className={cn('flex-1 overflow-y-auto py-4', compact ? 'px-2' : 'px-3')}>
                {Object.entries(groups).map(([group, items]) => (
                    <div key={group || 'default'} className="mb-6 last:mb-0">
                        {group && !compact && (
                            <div className="mb-2 px-3 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                {group}
                            </div>
                        )}
                        <ul className="space-y-1">
                            {items.map((item) => (
                                <li key={item.slug}>
                                    <Link
                                        href={item.url}
                                        title={compact ? item.label : undefined}
                                        className={cn(
                                            'flex items-center rounded-md text-sm font-medium transition-colors',
                                            'hover:bg-accent hover:text-accent-foreground',
                                            compact ? 'justify-center px-2 py-2' : 'px-3 py-2',
                                            activeSlug === item.slug
                                                ? 'bg-accent text-accent-foreground'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        <NavIcon name={item.icon} className={compact ? '' : 'mr-2'} />
                                        {!compact && <span>{item.label}</span>}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </nav>
        </>
    );

    return (
        <div className="flex min-h-screen bg-muted/30" style={buildThemeVars(panel.theme)}>
            <aside
                className={cn(
                    'relative hidden shrink-0 flex-col border-r bg-card transition-[width] duration-200 md:flex',
                    collapsed ? 'w-16' : 'w-64',
                )}
            >
                {renderNav(collapsed)}
                <button
                    type="button"
                    onClick={() => setCollapsed((v) => !v)}
                    className="absolute -right-3 top-20 hidden size-6 items-center justify-center rounded-full border bg-card text-muted-foreground shadow-sm hover:text-foreground md:flex"
                    aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                >
                    {collapsed ? (
                        <PanelLeftOpen className="size-3.5" />
                    ) : (
                        <PanelLeftClose className="size-3.5" />
                    )}
                </button>
            </aside>

            {mobileOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/40 md:hidden"
                    onClick={() => setMobileOpen(false)}
                    aria-hidden
                />
            )}
            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r bg-card transition-transform duration-200 md:hidden',
                    mobileOpen ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                <div className="flex items-center justify-between border-b px-6 py-4">
                    <span className="text-lg font-semibold tracking-tight">{panel.brand}</span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="size-8 p-0"
                        onClick={() => setMobileOpen(false)}
                        aria-label="Close menu"
                    >
                        <X className="size-4" />
                    </Button>
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
                                            <NavIcon name={item.icon} className="mr-2" />
                                            <span>{item.label}</span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </nav>
            </aside>

            <main className="flex-1 overflow-x-auto">
                <div className="flex h-14 items-center justify-between border-b bg-card px-4">
                    <div className="flex items-center md:hidden">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="size-9 p-0"
                            onClick={() => setMobileOpen(true)}
                            aria-label="Open menu"
                        >
                            <Menu className="size-5" />
                        </Button>
                        <span className="ml-2 text-base font-semibold tracking-tight">
                            {panel.brand}
                        </span>
                    </div>
                    <div className="hidden md:block" />
                    {panel.global_search.enabled && (
                        <GlobalSearchDialog
                            url={panel.global_search.url}
                            placeholder={panel.global_search.placeholder}
                        />
                    )}
                </div>
                <div className="mx-auto max-w-7xl p-4 md:p-8">{children}</div>
            </main>
            <Toaster />
        </div>
    );
}
