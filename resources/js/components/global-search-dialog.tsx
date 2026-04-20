import { router } from '@inertiajs/react';
import { Command } from 'cmdk';
import { Search } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { cn } from '../lib/utils';
import NavIcon from './nav-icon';

type SearchItem = {
    title: string;
    description?: string;
    url: string;
    icon?: string;
};

type SearchGroup = {
    resource: { label: string; icon?: string | null };
    items: SearchItem[];
};

type Props = {
    url: string;
    placeholder?: string;
    __?: (key: string, replacements?: Record<string, string | number>) => string;
};

export default function GlobalSearchDialog({ url, placeholder = 'Search...', __ = (key) => key }: Props) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [groups, setGroups] = useState<SearchGroup[]>([]);
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        function handleKey(e: KeyboardEvent) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setOpen((v) => !v);
            }
        }
        window.addEventListener('keydown', handleKey);
        return () => window.removeEventListener('keydown', handleKey);
    }, []);

    useEffect(() => {
        if (!open) {
            setQuery('');
            setGroups([]);
        }
    }, [open]);

    const search = useCallback(
        (q: string) => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
            if (!q.trim()) {
                setGroups([]);
                setLoading(false);
                return;
            }
            setLoading(true);
            debounceRef.current = setTimeout(async () => {
                try {
                    const res = await fetch(`${url}?q=${encodeURIComponent(q)}`, {
                        headers: { Accept: 'application/json' },
                    });
                    const data = await res.json();
                    setGroups(data.results ?? []);
                } catch {
                    setGroups([]);
                } finally {
                    setLoading(false);
                }
            }, 250);
        },
        [url],
    );

    function handleSelect(itemUrl: string) {
        setOpen(false);
        router.visit(itemUrl);
    }

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="flex items-center gap-2 rounded-md border bg-muted/50 px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-muted"
            >
                <Search className="size-3.5 shrink-0" />
                <span className="hidden sm:inline">{placeholder}</span>
                <kbd className="hidden rounded border bg-background px-1 py-0.5 font-mono text-xs sm:inline">
                    ⌘K
                </kbd>
            </button>

            {open && (
                <div className="fixed inset-0 z-50 flex items-start justify-center pt-[10vh]">
                    <div
                        className="fixed inset-0 bg-black/40"
                        onClick={() => setOpen(false)}
                        aria-hidden
                    />
                    <Command
                        className="relative z-50 w-full max-w-lg overflow-hidden rounded-xl border bg-card shadow-2xl"
                        shouldFilter={false}
                    >
                        <div className="flex items-center border-b px-3">
                            <Search className="me-2 size-4 shrink-0 text-muted-foreground" />
                            <Command.Input
                                value={query}
                                onValueChange={(v) => {
                                    setQuery(v);
                                    search(v);
                                }}
                                placeholder={placeholder}
                                className="flex h-12 w-full bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground"
                                autoFocus
                            />
                            {loading && (
                                <div className="size-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent" />
                            )}
                        </div>

                        <Command.List className="max-h-[400px] overflow-y-auto p-2">
                            {!loading && query && groups.length === 0 && (
                                <Command.Empty className="py-8 text-center text-sm text-muted-foreground">
                                    {__('No results found.')}
                                </Command.Empty>
                            )}

                            {!query && !loading && (
                                <div className="py-8 text-center text-sm text-muted-foreground">
                                    {__('Start typing to search…')}
                                </div>
                            )}

                            {groups.map((group) => (
                                <Command.Group
                                    key={group.resource.label}
                                    heading={
                                        <div className="flex items-center gap-1.5 px-2 pb-1 pt-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            <NavIcon name={group.resource.icon} className="size-3" />
                                            {group.resource.label}
                                        </div>
                                    }
                                    className="mb-2"
                                >
                                    {group.items.map((item) => (
                                        <Command.Item
                                            key={item.url}
                                            value={item.url}
                                            onSelect={() => handleSelect(item.url)}
                                            className={cn(
                                                'flex cursor-pointer flex-col rounded-md px-3 py-2 text-sm',
                                                'aria-selected:bg-accent aria-selected:text-accent-foreground',
                                                'hover:bg-accent hover:text-accent-foreground',
                                            )}
                                        >
                                            <span className="font-medium">{item.title}</span>
                                            {item.description && (
                                                <span className="text-xs text-muted-foreground">
                                                    {item.description}
                                                </span>
                                            )}
                                        </Command.Item>
                                    ))}
                                </Command.Group>
                            ))}
                        </Command.List>
                    </Command>
                </div>
            )}
        </>
    );
}
