import {
    endOfMonth,
    endOfYear,
    format,
    startOfMonth,
    startOfYear,
    subDays,
    subMonths,
} from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import * as React from 'react';
import { DateRange } from 'react-day-picker';

import { cn } from '../lib/utils';
import { Button } from './ui/button';
import { Calendar } from './ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from './ui/popover';

type Preset = {
    label: string;
    range: () => DateRange;
};

function buildPresets(): Preset[] {
    const today = new Date();
    return [
        { label: 'Today', range: () => ({ from: today, to: today }) },
        {
            label: 'Yesterday',
            range: () => {
                const y = subDays(today, 1);
                return { from: y, to: y };
            },
        },
        { label: 'Last 7 days', range: () => ({ from: subDays(today, 6), to: today }) },
        { label: 'Last 30 days', range: () => ({ from: subDays(today, 29), to: today }) },
        { label: 'This month', range: () => ({ from: startOfMonth(today), to: endOfMonth(today) }) },
        {
            label: 'Last month',
            range: () => {
                const prev = subMonths(today, 1);
                return { from: startOfMonth(prev), to: endOfMonth(prev) };
            },
        },
        { label: 'This year', range: () => ({ from: startOfYear(today), to: endOfYear(today) }) },
    ];
}

function formatRange(range: DateRange | undefined): string {
    if (!range?.from) return 'Pick a date range';
    if (range.to) return `${format(range.from, 'LLL dd, y')} – ${format(range.to, 'LLL dd, y')}`;
    return format(range.from, 'LLL dd, y');
}

function toISODate(d: Date | undefined): string {
    return d ? format(d, 'yyyy-MM-dd') : '';
}

function parseISODate(v: string | null | undefined): Date | undefined {
    if (!v) return undefined;
    const [y, m, d] = v.split('-').map(Number);
    if (!y || !m || !d) return undefined;
    return new Date(y, m - 1, d);
}

type DateRangePickerProps = {
    from: string;
    until: string;
    onChange: (from: string, until: string) => void;
    label?: string;
    className?: string;
};

export function DateRangePicker({ from, until, onChange, label, className }: DateRangePickerProps) {
    const [open, setOpen] = React.useState(false);
    const presets = React.useMemo(buildPresets, []);

    const range: DateRange | undefined = React.useMemo(() => {
        const f = parseISODate(from);
        const t = parseISODate(until);
        if (!f && !t) return undefined;
        return { from: f, to: t };
    }, [from, until]);

    const apply = (next: DateRange | undefined) => {
        onChange(toISODate(next?.from), toISODate(next?.to));
    };

    return (
        <div className={cn('flex flex-col gap-1', className)}>
            {label && (
                <label className="block text-xs font-medium text-muted-foreground">{label}</label>
            )}
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        variant="outline"
                        className={cn(
                            'h-9 w-[260px] justify-start text-left font-normal',
                            !range && 'text-muted-foreground',
                        )}
                    >
                        <CalendarIcon className="me-2 size-4" />
                        {formatRange(range)}
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="flex w-auto p-0" align="start">
                    <div className="flex flex-col gap-1 border-r p-2">
                        {presets.map((preset) => (
                            <Button
                                key={preset.label}
                                variant="ghost"
                                size="sm"
                                className="justify-start"
                                onClick={() => {
                                    apply(preset.range());
                                    setOpen(false);
                                }}
                            >
                                {preset.label}
                            </Button>
                        ))}
                        {range && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="justify-start text-muted-foreground"
                                onClick={() => {
                                    apply(undefined);
                                    setOpen(false);
                                }}
                            >
                                Clear
                            </Button>
                        )}
                    </div>
                    <Calendar
                        mode="range"
                        numberOfMonths={2}
                        defaultMonth={range?.from}
                        selected={range}
                        onSelect={apply}
                    />
                </PopoverContent>
            </Popover>
        </div>
    );
}
