import { ChevronDown, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '../lib/utils';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Popover, PopoverContent, PopoverTrigger } from './ui/popover';

type Option = { value: string; label: string };

type Props = {
    id: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string | null;
    disabled?: boolean;
    nullable?: boolean;
    lookupUrl: string;
};

export function SearchableSelect({
    id,
    value,
    onChange,
    placeholder,
    disabled,
    nullable,
    lookupUrl,
}: Props) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Option[]>([]);
    const [loading, setLoading] = useState(false);
    const [selectedLabel, setSelectedLabel] = useState<string | null>(null);
    const requestId = useRef(0);

    useEffect(() => {
        if (!value) {
            setSelectedLabel(null);
            return;
        }
        const rid = ++requestId.current;
        fetch(`${lookupUrl}?id=${encodeURIComponent(value)}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => r.json())
            .then((data: { results: Option[] }) => {
                if (rid !== requestId.current) return;
                const first = data.results?.[0];
                setSelectedLabel(first?.label ?? String(value));
            })
            .catch(() => {
                if (rid === requestId.current) setSelectedLabel(String(value));
            });
    }, [value, lookupUrl]);

    useEffect(() => {
        if (!open) return;
        const rid = ++requestId.current;
        setLoading(true);
        const handle = setTimeout(() => {
            fetch(`${lookupUrl}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
            })
                .then((r) => r.json())
                .then((data: { results: Option[] }) => {
                    if (rid !== requestId.current) return;
                    setResults(data.results ?? []);
                })
                .catch(() => {
                    if (rid === requestId.current) setResults([]);
                })
                .finally(() => {
                    if (rid === requestId.current) setLoading(false);
                });
        }, 180);
        return () => clearTimeout(handle);
    }, [open, query, lookupUrl]);

    const triggerLabel = selectedLabel ?? placeholder ?? 'Select...';

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    className={cn(
                        'w-full justify-between font-normal',
                        !selectedLabel && 'text-muted-foreground',
                    )}
                >
                    <span className="truncate">{triggerLabel}</span>
                    <span className="ml-2 flex items-center gap-1">
                        {nullable && value && (
                            <X
                                role="button"
                                aria-label="Clear"
                                className="size-4 opacity-60 hover:opacity-100"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    onChange('');
                                }}
                            />
                        )}
                        <ChevronDown className="size-4 opacity-60" />
                    </span>
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                <div className="border-b p-2">
                    <Input
                        autoFocus
                        placeholder="Search..."
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                    />
                </div>
                <div className="max-h-64 overflow-y-auto p-1">
                    {loading && (
                        <div className="p-2 text-xs text-muted-foreground">Searching…</div>
                    )}
                    {!loading && results.length === 0 && (
                        <div className="p-2 text-xs text-muted-foreground">No results.</div>
                    )}
                    {results.map((opt) => (
                        <button
                            key={opt.value}
                            type="button"
                            className={cn(
                                'flex w-full items-center rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground',
                                opt.value === value && 'bg-accent',
                            )}
                            onClick={() => {
                                onChange(opt.value);
                                setSelectedLabel(opt.label);
                                setOpen(false);
                                setQuery('');
                            }}
                        >
                            {opt.label}
                        </button>
                    ))}
                </div>
            </PopoverContent>
        </Popover>
    );
}
