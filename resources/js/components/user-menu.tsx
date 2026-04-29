import { router } from '@inertiajs/react';
import { ChevronsUpDown, LogOut, User as UserIcon } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from './ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from './ui/sidebar';

type Props = {
    user: { name: string; email: string };
    profileUrl: string | null;
    logoutUrl: string | null;
    __: (key: string) => string;
};

function initials(name: string): string {
    const parts = name.trim().split(/\s+/);
    const first = parts[0]?.[0] ?? '';
    const second = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return (first + second).toUpperCase() || '?';
}

export default function UserMenu({ user, profileUrl, logoutUrl, __ }: Props) {
    const { isMobile } = useSidebar();

    const logout = () => {
        if (!logoutUrl) return;
        router.post(logoutUrl);
    };

    const goToProfile = () => {
        if (!profileUrl) return;
        router.visit(profileUrl);
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            tooltip={user.name}
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground text-xs font-semibold">
                                {initials(user.name)}
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">{user.name}</span>
                                <span className="truncate text-xs text-muted-foreground">{user.email}</span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        side={isMobile ? 'bottom' : 'right'}
                        align="end"
                        sideOffset={4}
                        className="w-56"
                    >
                        <div className="flex items-center gap-2 px-2 py-1.5 text-sm">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-muted text-xs font-semibold">
                                {initials(user.name)}
                            </div>
                            <div className="grid flex-1 text-left leading-tight">
                                <span className="truncate font-medium">{user.name}</span>
                                <span className="truncate text-xs text-muted-foreground">{user.email}</span>
                            </div>
                        </div>
                        <DropdownMenuSeparator />
                        {profileUrl ? (
                            <DropdownMenuItem onSelect={goToProfile}>
                                <UserIcon className="size-4" />
                                {__('Profile')}
                            </DropdownMenuItem>
                        ) : null}
                        {logoutUrl ? (
                            <>
                                {profileUrl ? <DropdownMenuSeparator /> : null}
                                <DropdownMenuItem onSelect={logout} destructive>
                                    <LogOut className="size-4" />
                                    {__('Sign out')}
                                </DropdownMenuItem>
                            </>
                        ) : null}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
