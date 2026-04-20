import { Link } from '@inertiajs/react';
import React, { type ReactNode } from 'react';
import { useFlashToast } from '../hooks/use-flash-toast';
import { create__ } from '../lib/i18n';
import { cn } from '../lib/utils';
import GlobalSearchDialog from './global-search-dialog';
import LocaleSwitcher from './locale-switcher';
import NavIcon from './nav-icon';
import NotificationBell from './notification-bell';
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
} from './ui/sidebar';
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

type PanelNotifications = {
    enabled: boolean;
    urls: {
        index: string;
        recent: string;
        mark_all_read: string;
    };
};

type PanelProps = {
    id: string;
    brand: string;
    path: string;
    navigation: NavItem[];
    global_search: GlobalSearch;
    theme?: PanelTheme;
    notifications?: PanelNotifications;
    dashboard_columns: number;
    sidebar_collapsed?: boolean;
    sidebar_collapsible?: boolean;
    locale: string;
    available_locales: string[];
    translations: Record<string, string>;
};

const RTL_LOCALES = ['ar', 'he', 'fa', 'ur'];

type Props = {
    panel: PanelProps;
    activeSlug?: string;
    children: ReactNode;
};

const DENSITY_VARS: Record<string, Record<string, string>> = {
    compact: { '--rocket-gap': '0.5rem', '--rocket-input-height': '2rem', '--rocket-font-size': '0.8125rem' },
    default: { '--rocket-gap': '0.75rem', '--rocket-input-height': '2.5rem', '--rocket-font-size': '0.875rem' },
    comfortable: { '--rocket-gap': '1rem', '--rocket-input-height': '3rem', '--rocket-font-size': '1rem' },
};

function buildThemeVars(theme: PanelTheme | undefined): React.CSSProperties {
    const vars: Record<string, string> = {};
    if (theme?.primary) vars['--primary'] = theme.primary;
    if (theme?.accent) vars['--accent'] = theme.accent;
    if (theme?.radius) vars['--radius'] = theme.radius;
    if (theme?.font) vars['--font-sans'] = `"${theme.font}", sans-serif`;
    Object.assign(vars, DENSITY_VARS[theme?.density ?? 'default'] ?? DENSITY_VARS.default);
    return vars as unknown as React.CSSProperties;
}

export default function PanelShell({ panel, activeSlug, children }: Props) {
    useFlashToast();

    const __ = create__(panel.translations);
    const dir = RTL_LOCALES.includes(panel.locale) ? 'rtl' : 'ltr';
    const side = dir === 'rtl' ? 'right' : 'left';
    const collapsible = panel.sidebar_collapsible === false ? 'none' : 'icon';

    const groups = panel.navigation.reduce<Record<string, NavItem[]>>((acc, item) => {
        const key = item.group ?? '';
        (acc[key] ||= []).push(item);
        return acc;
    }, {});

    return (
        <div dir={dir} style={buildThemeVars(panel.theme)}>
            <SidebarProvider defaultOpen={!(panel.sidebar_collapsed ?? false)}>
                <Sidebar side={side} collapsible={collapsible}>
                    <SidebarHeader>
                        <div
                            className={cn(
                                'flex h-12 items-center px-2',
                                'group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0',
                            )}
                        >
                            <span className="text-lg font-semibold tracking-tight group-data-[collapsible=icon]:hidden">
                                {panel.brand}
                            </span>
                            <span className="hidden text-lg font-semibold tracking-tight group-data-[collapsible=icon]:inline">
                                {panel.brand.slice(0, 1)}
                            </span>
                        </div>
                    </SidebarHeader>
                    <SidebarContent>
                        {Object.entries(groups).map(([group, items]) => (
                            <SidebarGroup key={group || 'default'}>
                                {group && <SidebarGroupLabel>{group}</SidebarGroupLabel>}
                                <SidebarGroupContent>
                                    <SidebarMenu>
                                        {items.map((item) => (
                                            <SidebarMenuItem key={item.slug}>
                                                <SidebarMenuButton
                                                    asChild
                                                    isActive={activeSlug === item.slug}
                                                    tooltip={item.label}
                                                >
                                                    <Link href={item.url}>
                                                        <NavIcon name={item.icon} />
                                                        <span>{item.label}</span>
                                                    </Link>
                                                </SidebarMenuButton>
                                            </SidebarMenuItem>
                                        ))}
                                    </SidebarMenu>
                                </SidebarGroupContent>
                            </SidebarGroup>
                        ))}
                    </SidebarContent>
                    <SidebarRail />
                </Sidebar>
                <SidebarInset>
                    <header className="flex h-14 items-center justify-between gap-2 border-b bg-card px-4">
                        <div className="flex items-center gap-2">
                            <SidebarTrigger />
                            <span className="text-base font-semibold tracking-tight md:hidden">
                                {panel.brand}
                            </span>
                        </div>
                        <div className="flex items-center gap-1">
                            {panel.global_search.enabled && (
                                <GlobalSearchDialog
                                    url={panel.global_search.url}
                                    placeholder={panel.global_search.placeholder}
                                    __={__}
                                />
                            )}
                            {panel.available_locales.length > 1 && (
                                <LocaleSwitcher
                                    locale={panel.locale}
                                    availableLocales={panel.available_locales}
                                    switchUrl={`/${panel.path.replace(/^\/+|\/+$/g, '')}/locale`}
                                />
                            )}
                            {panel.notifications?.enabled && panel.notifications.urls.index && (
                                <NotificationBell urls={panel.notifications.urls} __={__} />
                            )}
                        </div>
                    </header>
                    <div className="mx-auto w-full max-w-7xl p-4 md:p-8">{children}</div>
                </SidebarInset>
            </SidebarProvider>
            <Toaster />
        </div>
    );
}
