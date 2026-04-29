import { router } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Filter as FilterIcon, X } from 'lucide-react';
import { useState } from 'react';
import { DateRangePicker } from './date-range-picker';
import FormField, { type FieldSchema } from './form-field';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Card } from './ui/card';
import { Popover, PopoverContent, PopoverTrigger } from './ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from './ui/select';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from './ui/sheet';
import QueryBuilderTree, { type ConstraintSchema, type QbState } from './query-builder';

export type FiltersLayout =
    | 'dropdown'
    | 'above_content'
    | 'above_content_collapsible'
    | 'below_content'
    | 'left_sidebar'
    | 'right_sidebar';

export type FiltersLayoutSchema = {
    layout: FiltersLayout;
    columns: number;
    width: 'sm' | 'md' | 'lg' | 'xl';
    defer: boolean;
    trigger_label: string | null;
};

type Indicator = { label: string; clear_keys: string[] };

export type TableFilterSchema =
    | {
          type: 'select';
          name: string;
          query_key: string;
          state_key: string;
          label: string;
          options: Record<string, string>;
          value: string | string[] | null | undefined;
          visible_in_dropdown?: boolean;
          active_indicators?: Indicator[];
      }
    | {
          type: 'ternary';
          name: string;
          query_key: string;
          state_key: string;
          label: string;
          value: string | string[] | null | undefined;
          visible_in_dropdown?: boolean;
          active_indicators?: Indicator[];
      }
    | {
          type: 'date_range';
          name: string;
          label: string;
          from_key: string;
          until_key: string;
          from_state_key: string;
          until_state_key: string;
          from: string | string[] | null | undefined;
          until: string | string[] | null | undefined;
          visible_in_dropdown?: boolean;
          active_indicators?: Indicator[];
      }
    | {
          type: 'trashed';
          name: string;
          query_key: string;
          state_key: string;
          label: string;
          options: Record<string, string>;
          value: string | string[] | null | undefined;
          visible_in_dropdown?: boolean;
          active_indicators?: Indicator[];
      }
    | {
          type: 'custom';
          name: string;
          label: string;
          state_namespace: string;
          state: Record<string, unknown>;
          form: FieldSchema[];
          visible_in_dropdown?: boolean;
          active_indicators?: Indicator[];
      }
    | {
          type: 'toggle';
          name: string;
          label: string;
          state_namespace: string;
          state: Record<string, unknown>;
          form: never[];
          visible_in_dropdown?: boolean;
          active_indicators?: Indicator[];
      }
    | {
          type: 'query_builder';
          name: string;
          label: string;
          state_key: string;
          state: QbState;
          constraints: ConstraintSchema[];
          rule_count: number;
          visible_in_dropdown?: boolean;
          active_indicators?: Indicator[];
      };

type Props = {
    filters: TableFilterSchema[];
    layout?: FiltersLayoutSchema;
    query: Record<string, unknown>;
    baseUrl: string;
    paramPrefix?: string;
    /** When true, skip rendering the active-filter indicator bar (caller renders it elsewhere). */
    hideIndicators?: boolean;
    __?: (key: string, replacements?: Record<string, string | number>) => string;
};

function qString(val: unknown): string {
    if (val === null || val === undefined) return '';
    if (Array.isArray(val)) return val.length ? String(val[0]) : '';
    return String(val);
}

/** Set or clear a deeply-nested key (dot path) on a query object. */
function setDeep(obj: Record<string, unknown>, path: string, value: unknown): Record<string, unknown> {
    const parts = path.split('.');
    const next: Record<string, unknown> = { ...obj };
    let cursor: Record<string, unknown> = next;
    for (let i = 0; i < parts.length - 1; i++) {
        const key = parts[i];
        const existing = cursor[key];
        const fresh: Record<string, unknown> =
            existing && typeof existing === 'object' && !Array.isArray(existing)
                ? { ...(existing as Record<string, unknown>) }
                : {};
        cursor[key] = fresh;
        cursor = fresh;
    }
    const last = parts[parts.length - 1];
    if (value === null || value === undefined || value === '') {
        delete cursor[last];
    } else {
        cursor[last] = value;
    }
    return next;
}

function clearKeys(query: Record<string, unknown>, keys: string[]): Record<string, unknown> {
    let next = { ...query };
    for (const k of keys) {
        if (k.includes('.')) {
            next = setDeep(next, k, null);
        } else {
            delete next[k];
        }
    }
    return next;
}

export default function TableFilters({
    filters,
    layout,
    query,
    baseUrl,
    paramPrefix = '',
    hideIndicators = false,
    __ = (key) => key,
}: Props) {
    const layoutKind: FiltersLayout = layout?.layout ?? 'dropdown';
    const defer = layout?.defer ?? layoutKind === 'dropdown';
    const columns = layout?.columns ?? 1;

    const [pendingPatches, setPendingPatches] = useState<Record<string, unknown>>({});

    if (filters.length === 0) return null;

    const allIndicators: Indicator[] = filters.flatMap((f) => f.active_indicators ?? []);
    const activeCount = allIndicators.length;

    const navigate = (mutator: (q: Record<string, unknown>) => Record<string, unknown>) => {
        const next = mutator(query);
        next[paramPrefix ? paramPrefix + 'page' : 'page'] = 1;
        router.get(baseUrl, next, { preserveState: true, preserveScroll: true, replace: true });
    };

    const commitPending = () => {
        navigate((q) => {
            let next = { ...q };
            for (const [path, val] of Object.entries(pendingPatches)) {
                next = setDeep(next, path, val);
            }
            return next;
        });
        setPendingPatches({});
    };

    const stagePatch = (path: string, value: unknown) => {
        if (defer) {
            setPendingPatches((p) => ({ ...p, [path]: value }));
            return;
        }
        navigate((q) => setDeep(q, path, value));
    };

    const resetAll = () => {
        navigate((q) => clearKeys(q, allIndicators.flatMap((i) => i.clear_keys)));
        setPendingPatches({});
    };

    const filterFields = (
        <div
            className="grid gap-3"
            style={{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }}
        >
            {filters.map((f) => renderFilter(f, query, pendingPatches, stagePatch, navigate, __))}
        </div>
    );

    const indicatorBar = !hideIndicators && activeCount > 0 && (
        <div className="flex flex-wrap items-center gap-2">
            {allIndicators.map((ind, idx) => (
                <Badge
                    key={idx}
                    variant="secondary"
                    className="gap-1 pr-1"
                >
                    <span>{ind.label}</span>
                    <button
                        type="button"
                        onClick={() => navigate((q) => clearKeys(q, ind.clear_keys))}
                        className="rounded hover:bg-muted-foreground/20"
                        aria-label={__('Remove filter')}
                    >
                        <X className="size-3" />
                    </button>
                </Badge>
            ))}
            <Button type="button" size="sm" variant="ghost" onClick={resetAll}>
                {__('Reset all')}
            </Button>
        </div>
    );

    const applyBar = defer && Object.keys(pendingPatches).length > 0 && (
        <div className="flex justify-end gap-2 pt-2">
            <Button type="button" size="sm" variant="ghost" onClick={() => setPendingPatches({})}>
                {__('Discard')}
            </Button>
            <Button type="button" size="sm" onClick={commitPending}>
                {__('Apply')}
            </Button>
        </div>
    );

    if (layoutKind === 'dropdown') {
        return (
            <div className="space-y-3">
                <div className="flex flex-wrap items-center gap-2">
                    <Popover>
                        <PopoverTrigger asChild>
                            <Button type="button" variant="outline">
                                <FilterIcon className="me-2 size-4" />
                                {layout?.trigger_label ?? __('Filters')}
                                {activeCount > 0 && (
                                    <Badge variant="secondary" className="ms-2">
                                        {activeCount}
                                    </Badge>
                                )}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent
                            align="start"
                            className={popoverWidth(layout?.width ?? 'sm')}
                        >
                            <div className="space-y-3">
                                {filterFields}
                                {applyBar}
                            </div>
                        </PopoverContent>
                    </Popover>
                    {indicatorBar}
                </div>
            </div>
        );
    }

    // Inline layouts (above_content, above_content_collapsible, below_content, sidebar fallbacks)
    const collapsible = layoutKind === 'above_content_collapsible';
    const isAbove = layoutKind === 'above_content' || collapsible;
    const inner = (
        <CollapsibleWrapper collapsible={collapsible} __={__}>
            {indicatorBar}
            {filterFields}
            {applyBar}
        </CollapsibleWrapper>
    );

    if (isAbove) {
        return <Card className="gap-0 p-4">{inner}</Card>;
    }

    return inner;
}

function popoverWidth(w: 'sm' | 'md' | 'lg' | 'xl'): string {
    return {
        sm: 'w-80',
        md: 'w-96',
        lg: 'w-[32rem]',
        xl: 'w-[40rem]',
    }[w];
}

function CollapsibleWrapper({
    collapsible,
    __,
    children,
}: {
    collapsible: boolean;
    __: (k: string) => string;
    children: React.ReactNode;
}) {
    const [open, setOpen] = useState(!collapsible);
    if (!collapsible) {
        return <div className="space-y-3">{children}</div>;
    }
    return (
        <div className="space-y-3">
            <Button
                type="button"
                variant="outline"
                onClick={() => setOpen((o) => !o)}
            >
                <FilterIcon className="me-2 size-4" />
                {__('Filters')}
                {open ? <ChevronUp className="ms-2 size-4" /> : <ChevronDown className="ms-2 size-4" />}
            </Button>
            {open && children}
        </div>
    );
}

function renderFilter(
    f: TableFilterSchema,
    query: Record<string, unknown>,
    pending: Record<string, unknown>,
    stage: (path: string, value: unknown) => void,
    navigate: (m: (q: Record<string, unknown>) => Record<string, unknown>) => void,
    __: (key: string, replacements?: Record<string, string | number>) => string,
) {
    const pendingFor = (path: string) => (path in pending ? pending[path] : undefined);

    if (f.type === 'select') {
        const current = pendingFor(f.state_key) ?? f.value;
        return (
            <div key={f.name} className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">{f.label}</label>
                <Select
                    value={qString(current) || '__all__'}
                    onValueChange={(v) => stage(f.state_key, v === '__all__' ? null : v)}
                >
                    <SelectTrigger className="h-8 w-full">
                        <SelectValue placeholder={__('All')} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="__all__">{__('All')}</SelectItem>
                        {Object.entries(f.options).map(([k, label]) => (
                            <SelectItem key={k} value={k}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        );
    }
    if (f.type === 'ternary') {
        const current = pendingFor(f.state_key) ?? f.value;
        return (
            <div key={f.name} className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">{f.label}</label>
                <Select
                    value={qString(current) || 'all'}
                    onValueChange={(v) => stage(f.state_key, v === 'all' ? null : v)}
                >
                    <SelectTrigger className="h-8 w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">{__('All')}</SelectItem>
                        <SelectItem value="yes">{__('Yes')}</SelectItem>
                        <SelectItem value="no">{__('No')}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        );
    }
    if (f.type === 'trashed') {
        const current = pendingFor(f.state_key) ?? f.value;
        return (
            <div key={f.name} className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">{f.label}</label>
                <Select
                    value={qString(current) || 'without'}
                    onValueChange={(v) => stage(f.state_key, v)}
                >
                    <SelectTrigger className="h-8 w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {Object.entries(f.options).map(([k, label]) => (
                            <SelectItem key={k} value={k}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        );
    }
    if (f.type === 'date_range') {
        const from = pendingFor(f.from_state_key) ?? f.from;
        const until = pendingFor(f.until_state_key) ?? f.until;
        return (
            <DateRangePicker
                key={f.name}
                label={f.label}
                from={qString(from)}
                until={qString(until)}
                onChange={(fromVal, untilVal) => {
                    stage(f.from_state_key, fromVal || null);
                    stage(f.until_state_key, untilVal || null);
                }}
            />
        );
    }
    if (f.type === 'toggle') {
        const path = f.state_namespace;
        const current = pendingFor(path) ?? (f.state['__value'] as unknown);
        const checked = current === '1' || current === true || current === 'true';
        return (
            <label key={f.name} className="flex cursor-pointer items-center gap-2">
                <input
                    type="checkbox"
                    checked={checked}
                    onChange={(e) => stage(path, e.target.checked ? '1' : null)}
                />
                <span className="text-sm">{f.label}</span>
            </label>
        );
    }
    if (f.type === 'custom') {
        return (
            <div key={f.name} className="space-y-2 rounded-md border border-border p-3">
                <div className="text-sm font-medium">{f.label}</div>
                <div className="space-y-2">
                    {f.form.map((field) => {
                        const path = `${f.state_namespace}.${field.name}`;
                        const value = pendingFor(path) ?? (f.state[field.name] as unknown);
                        return (
                            <FormField
                                key={field.name}
                                field={field}
                                value={value}
                                onChange={(v) => stage(path, v as unknown)}
                                __={__}
                            />
                        );
                    })}
                </div>
            </div>
        );
    }
    if (f.type === 'query_builder') {
        return (
            <QueryBuilderLauncher
                key={f.name}
                filter={f}
                onApply={(state) =>
                    navigate((q) => setDeep(q, f.state_key, JSON.stringify(state)))
                }
                __={__}
            />
        );
    }
    return null;
}

function QueryBuilderLauncher({
    filter,
    onApply,
    __,
}: {
    filter: Extract<TableFilterSchema, { type: 'query_builder' }>;
    onApply: (state: QbState) => void;
    __: (k: string) => string;
}) {
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<QbState>(filter.state);

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button type="button" variant="outline" className="w-full">
                    <FilterIcon className="me-2 size-4" />
                    {filter.label}
                    {filter.rule_count > 0 && (
                        <Badge variant="secondary" className="ms-2">
                            {filter.rule_count}
                        </Badge>
                    )}
                </Button>
            </SheetTrigger>
            <SheetContent className="w-full overflow-y-auto sm:max-w-[56rem]">
                <SheetHeader>
                    <SheetTitle>{filter.label}</SheetTitle>
                </SheetHeader>
                <div className="px-4">
                    <QueryBuilderTree
                        state={draft}
                        constraints={filter.constraints}
                        onChange={setDraft}
                        __={__}
                    />
                </div>
                <SheetFooter className="flex-row items-center justify-end gap-2 border-t">
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => {
                            setDraft({ logic: 'and', rules: [] });
                            onApply({ logic: 'and', rules: [] });
                            setOpen(false);
                        }}
                    >
                        {__('Clear')}
                    </Button>
                    <Button
                        type="button"
                        onClick={() => {
                            onApply(draft);
                            setOpen(false);
                        }}
                    >
                        {__('Apply')}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
