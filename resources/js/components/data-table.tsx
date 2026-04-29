import { Link, router } from '@inertiajs/react';
import {
    Check,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Copy,
    Eye,
    MoreHorizontal,
    Pencil,
    Search,
    Trash2,
    X,
} from 'lucide-react';
import { DynamicIcon, type IconName } from 'lucide-react/dynamic';
import { useEffect, useMemo, useState } from 'react';
import ConfirmDialog from './confirm-dialog';
import DataTableColumnHeader from './data-table-column-header';
import DataTableViewOptions from './data-table-view-options';
import TableFilters, { type FiltersLayoutSchema, type TableFilterSchema as TFSchema } from './table-filters';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from './ui/dropdown-menu';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Card } from './ui/card';
import { Input } from './ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from './ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from './ui/table';

type Column = {
    type: 'text' | 'badge' | string;
    name: string;
    label: string;
    sortable: boolean;
    toggleable?: boolean;
    toggled_hidden_by_default?: boolean;
    extra: Record<string, unknown>;
};

type PaginationStyle = 'simple' | 'numbered' | 'compact';

type Schema = {
    columns: Column[];
    searchable: boolean;
    default_sort: string | null;
    default_sort_direction: 'asc' | 'desc';
    pagination_style?: PaginationStyle;
    filters_layout?: FiltersLayoutSchema;
};

type Row = Record<string, unknown> & { _key: string | number };

const badgeColorClasses: Record<string, string> = {
    slate: 'border-transparent bg-slate-100 text-slate-800 dark:bg-slate-500/20 dark:text-slate-300',
    gray: 'border-transparent bg-gray-100 text-gray-800 dark:bg-gray-500/20 dark:text-gray-300',
    red: 'border-transparent bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300',
    orange: 'border-transparent bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-300',
    amber: 'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
    yellow: 'border-transparent bg-yellow-100 text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-300',
    green: 'border-transparent bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-300',
    emerald: 'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
    teal: 'border-transparent bg-teal-100 text-teal-800 dark:bg-teal-500/20 dark:text-teal-300',
    cyan: 'border-transparent bg-cyan-100 text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-300',
    blue: 'border-transparent bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300',
    indigo: 'border-transparent bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-300',
    violet: 'border-transparent bg-violet-100 text-violet-800 dark:bg-violet-500/20 dark:text-violet-300',
    purple: 'border-transparent bg-purple-100 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300',
    pink: 'border-transparent bg-pink-100 text-pink-800 dark:bg-pink-500/20 dark:text-pink-300',
    rose: 'border-transparent bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300',
};

type Pagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type ListFilters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
    per_page: number;
};

type RowActionSchema = {
    name: string;
    label: string;
    requires_confirmation: boolean;
    destructive: boolean;
    icon: string | null;
    scope: string;
    link?: boolean;
    route_suffix?: string | null;
    ability?: string | null;
};

type TableFilterSchema = TFSchema;

type Props = {
    schema: Schema;
    records: Row[];
    pagination: Pagination;
    filters: ListFilters;
    /** Full query string params to preserve across navigations */
    query: Record<string, unknown>;
    baseUrl: string;
    editable?: boolean;
    rowActions?: RowActionSchema[];
    rowActionsOverflowAfter?: number;
    bulkActions?: RowActionSchema[];
    tableFilters?: TableFilterSchema[];
    perPageOptions?: number[];
    /** If set, prefixes the 5 core query keys (page, search, sort, direction, per_page) when navigating. */
    paramPrefix?: string;
    __?: (key: string, replacements?: Record<string, string | number>) => string;
};

function qString(val: unknown): string {
    if (val === null || val === undefined) return '';
    if (Array.isArray(val)) return val.length ? String(val[0]) : '';
    return String(val);
}

export default function DataTable({
    schema,
    records,
    pagination,
    filters,
    query,
    baseUrl,
    editable = false,
    rowActions = [],
    rowActionsOverflowAfter = 3,
    bulkActions = [],
    tableFilters = [],
    perPageOptions = [10, 25, 50, 100],
    paramPrefix = '',
    __ = (key, replacements) => {
        let str = key;
        if (replacements) {
            const entries = Object.entries(replacements).sort(
                ([a], [b]) => b.length - a.length,
            );
            for (const [k, v] of entries) {
                str = str.replaceAll(`:${k}`, String(v));
            }
        }
        return str;
    },
}: Props) {
    const prefixKey = (k: string) => (paramPrefix ? paramPrefix + k : k);
    const coreKeys = new Set(['page', 'search', 'sort', 'direction', 'per_page', 'hidden']);
    const hasBulk = bulkActions.length > 0;
    const [selected, setSelected] = useState<Set<string>>(() => new Set());
    const [search, setSearch] = useState(filters.search);
    const [confirmAction, setConfirmAction] = useState<{
        kind: 'row' | 'bulk';
        action: RowActionSchema;
        row?: Row;
    } | null>(null);

    const perPageStorageKey = `rocket.per_page:${baseUrl}${paramPrefix ? `:${paramPrefix}` : ''}`;

    const navigate = (patch: Record<string, unknown> & { page?: number }) => {
        if (typeof patch.per_page === 'number' && typeof window !== 'undefined') {
            try {
                window.localStorage.setItem(perPageStorageKey, String(patch.per_page));
            } catch {
                // ignore storage failures (private mode, quota)
            }
        }
        const next: Record<string, unknown> = { ...query };
        for (const [k, v] of Object.entries(patch)) {
            const key = coreKeys.has(k) ? prefixKey(k) : k;
            if (v === undefined || v === '') {
                delete next[key];
            } else {
                next[key] = v;
            }
        }
        if (patch.page === undefined) {
            next[prefixKey('page')] = 1;
        }
        router.get(baseUrl, next, { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (typeof window === 'undefined') return;
        if (query[prefixKey('per_page')] !== undefined) return;
        let stored: string | null = null;
        try {
            stored = window.localStorage.getItem(perPageStorageKey);
        } catch {
            return;
        }
        if (!stored) return;
        const n = Number(stored);
        if (!Number.isFinite(n) || n <= 0) return;
        if (!perPageOptions.includes(n)) return;
        if (n === pagination.per_page) return;
        navigate({ per_page: n });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const hiddenKey = prefixKey('hidden');
    const hiddenFromUrl = qString(query[hiddenKey]);
    const defaultHidden = useMemo(
        () => schema.columns.filter((c) => c.toggled_hidden_by_default).map((c) => c.name),
        [schema.columns],
    );
    const hiddenColumns = useMemo<Set<string>>(() => {
        if (hiddenFromUrl === '') {
            return new Set(defaultHidden);
        }
        if (hiddenFromUrl === '-') {
            return new Set();
        }
        return new Set(hiddenFromUrl.split(',').filter(Boolean));
    }, [hiddenFromUrl, defaultHidden]);

    const toggleableColumns = useMemo(
        () =>
            schema.columns
                .filter((c) => c.toggleable)
                .map((c) => ({ name: c.name, label: c.label, visible: !hiddenColumns.has(c.name) })),
        [schema.columns, hiddenColumns],
    );

    const visibleColumns = useMemo(
        () => schema.columns.filter((c) => !hiddenColumns.has(c.name)),
        [schema.columns, hiddenColumns],
    );

    const toggleColumnVisibility = (name: string, visible: boolean) => {
        const next = new Set(hiddenColumns);
        if (visible) next.delete(name);
        else next.add(name);
        const arr = Array.from(next);
        const sameAsDefault =
            arr.length === defaultHidden.length && arr.every((n) => defaultHidden.includes(n));
        navigate({ hidden: sameAsDefault ? '' : arr.length === 0 ? '-' : arr.join(',') });
    };

    const setSort = (column: Column, direction: 'asc' | 'desc') => {
        if (!column.sortable) return;
        navigate({ sort: column.name, direction });
    };

    const layoutKind = schema.filters_layout?.layout ?? 'dropdown';
    const isDropdownLayout = layoutKind === 'dropdown';
    const filtersAbove = !isDropdownLayout && layoutKind !== 'below_content';

    type Indicator = { label: string; clear_keys: string[] };
    const allIndicators: Indicator[] = isDropdownLayout
        ? tableFilters.flatMap((f) => (f as { active_indicators?: Indicator[] }).active_indicators ?? [])
        : [];

    const clearIndicatorKeys = (keys: string[]) => {
        const next: Record<string, unknown> = { ...query };
        for (const key of keys) {
            if (!key.includes('.')) {
                delete next[key];
                continue;
            }
            const parts = key.split('.');
            let cursor = next;
            const trail: Array<{ obj: Record<string, unknown>; key: string }> = [];
            let valid = true;
            for (let i = 0; i < parts.length - 1; i++) {
                const k = parts[i];
                const child = cursor[k];
                if (!child || typeof child !== 'object' || Array.isArray(child)) {
                    valid = false;
                    break;
                }
                const fresh = { ...(child as Record<string, unknown>) };
                cursor[k] = fresh;
                trail.push({ obj: cursor, key: k });
                cursor = fresh;
            }
            if (!valid) continue;
            delete cursor[parts[parts.length - 1]];
            for (let i = trail.length - 1; i >= 0; i--) {
                const { obj, key: k } = trail[i];
                const child = obj[k] as Record<string, unknown>;
                if (Object.keys(child).length === 0) delete obj[k];
            }
        }
        next[prefixKey('page')] = 1;
        router.get(baseUrl, next, { preserveState: true, preserveScroll: true, replace: true });
    };

    const clearAllIndicators = () =>
        clearIndicatorKeys(allIndicators.flatMap((i) => i.clear_keys));

    const selectionColumn = hasBulk ? 1 : 0;
    const actionColumn =
        (editable ? 1 : 0) + (rowActions.filter((a) => a.scope === 'row').length > 0 ? 1 : 0);
    const totalColumns = visibleColumns.length + selectionColumn + actionColumn;

    const allIds = useMemo(() => records.map((r) => String(r._key)), [records]);
    const allSelected = hasBulk && records.length > 0 && selected.size === records.length;

    const toggleAll = () => {
        if (allSelected) {
            setSelected(new Set());
        } else {
            setSelected(new Set(allIds));
        }
    };

    const toggleRow = (id: string) => {
        const next = new Set(selected);
        if (next.has(id)) next.delete(id);
        else next.add(id);
        setSelected(next);
    };

    const runRowAction = (action: RowActionSchema, row: Row) => {
        if (action.requires_confirmation) {
            setConfirmAction({ kind: 'row', action, row });
            return;
        }
        submitRowAction(action, row);
    };

    const submitRowAction = (action: RowActionSchema, row: Row) => {
        const url = `${baseUrl}/${String(row._key)}/actions/${action.name}`;
        router.post(url, {});
    };

    const submitBulkAction = (action: RowActionSchema) => {
        const url = `${baseUrl}/bulk-actions/${action.name}`;
        router.post(url, { ids: Array.from(selected) });
    };

    const bulkDelete = bulkActions.find((a) => a.name === 'bulk-delete');

    const filtersBlock = tableFilters.length > 0 && (
        <TableFilters
            filters={tableFilters as TFSchema[]}
            layout={schema.filters_layout}
            query={query}
            baseUrl={baseUrl}
            paramPrefix={paramPrefix}
            hideIndicators={isDropdownLayout}
            __={__}
        />
    );

    return (
        <div className="space-y-4">
            {filtersAbove && filtersBlock}

            <div className="flex flex-wrap items-center gap-4">
                {schema.searchable && (
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            navigate({ search });
                        }}
                        className="flex max-w-sm flex-1 items-center gap-2"
                    >
                        <div className="relative flex-1">
                            <Search className="absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder={__('Search...')}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="h-8 ps-9"
                            />
                        </div>
                        <Button type="submit" variant="secondary">
                            {__('Search')}
                        </Button>
                    </form>
                )}

                <div className="ms-auto flex items-center gap-2">
                    {isDropdownLayout && tableFilters.length > 0 && filtersBlock}
                    {toggleableColumns.length > 0 && (
                        <DataTableViewOptions
                            columns={toggleableColumns}
                            onToggle={toggleColumnVisibility}
                            __={__}
                        />
                    )}
                </div>
            </div>

            {((hasBulk && bulkDelete && selected.size > 0) || allIndicators.length > 0) && (
                <div className="flex flex-wrap items-center gap-2 rounded-md border border-border bg-muted/40 px-3 py-2 text-sm">
                    {hasBulk && bulkDelete && selected.size > 0 && (
                        <>
                            <span className="text-muted-foreground">
                                {__(':count selected', { count: selected.size })}
                            </span>
                            <Button
                                type="button"
                                size="sm"
                                variant="destructive"
                                onClick={() =>
                                    bulkDelete.requires_confirmation
                                        ? setConfirmAction({ kind: 'bulk', action: bulkDelete })
                                        : submitBulkAction(bulkDelete)
                                }
                            >
                                {bulkDelete.label}
                            </Button>
                            {allIndicators.length > 0 && (
                                <span className="mx-1 h-5 w-px bg-border" aria-hidden />
                            )}
                        </>
                    )}
                    {allIndicators.map((ind, idx) => (
                        <Badge key={idx} variant="secondary" className="gap-1 pe-1">
                            <span>{ind.label}</span>
                            <button
                                type="button"
                                onClick={() => clearIndicatorKeys(ind.clear_keys)}
                                className="rounded hover:bg-muted-foreground/20"
                                aria-label={__('Remove filter')}
                            >
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))}
                    {allIndicators.length > 0 && (
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onClick={clearAllIndicators}
                            className="ms-auto"
                        >
                            {__('Reset all')}
                        </Button>
                    )}
                </div>
            )}

            <Card className="overflow-hidden p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {hasBulk && (
                                <TableHead className="w-10">
                                    <input
                                        type="checkbox"
                                        checked={allSelected}
                                        onChange={toggleAll}
                                        aria-label={__('Select all')}
                                        className="size-4 rounded border"
                                    />
                                </TableHead>
                            )}
                            {visibleColumns.map((col) => (
                                <TableHead key={col.name}>
                                    <DataTableColumnHeader
                                        label={col.label}
                                        name={col.name}
                                        sortable={col.sortable}
                                        toggleable={Boolean(col.toggleable)}
                                        activeSort={filters.sort || null}
                                        activeDirection={filters.direction}
                                        onSort={(direction) => setSort(col, direction)}
                                        onHide={
                                            col.toggleable
                                                ? () => toggleColumnVisibility(col.name, false)
                                                : undefined
                                        }
                                        __={__}
                                    />
                                </TableHead>
                            ))}
                            {(editable || rowActions.length > 0) && (
                                <TableHead className="w-24 text-end">{__('Actions')}</TableHead>
                            )}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {records.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={totalColumns}
                                    className="h-24 text-center text-muted-foreground"
                                >
                                    {__('No records found.')}
                                </TableCell>
                            </TableRow>
                        ) : (
                            records.map((row) => (
                                <TableRow key={String(row._key)}>
                                    {hasBulk && (
                                        <TableCell>
                                            <input
                                                type="checkbox"
                                                checked={selected.has(String(row._key))}
                                                onChange={() => toggleRow(String(row._key))}
                                                aria-label={__('Select row')}
                                                className="size-4 rounded border"
                                            />
                                        </TableCell>
                                    )}
                                    {visibleColumns.map((col) => (
                                        <TableCell key={col.name}>
                                            {renderCell(col, row[col.name], __)}
                                        </TableCell>
                                    ))}
                                    {(editable || rowActions.length > 0) && (
                                        <TableCell className="text-end">
                                            <div className="flex justify-end gap-1">
                                                {editable &&
                                                    Boolean(row._can_update) &&
                                                    !rowActions.some((a) => a.name === 'edit') && (
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="ghost"
                                                            className="size-8 p-0"
                                                        >
                                                            <Link
                                                                href={`${baseUrl}/${String(row._key)}/edit`}
                                                                aria-label={__('Edit')}
                                                            >
                                                                <Pencil className="size-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
                                                {(() => {
                                                    const visible = rowActions
                                                        .filter((a) => a.scope === 'row')
                                                        .filter((action) => {
                                                            if (action.ability) {
                                                                const flag = `_can_${action.ability}` as const;
                                                                return Boolean(row[flag]);
                                                            }
                                                            if (action.name === 'delete') return Boolean(row._can_delete);
                                                            return true;
                                                        });
                                                    const inline = visible.slice(0, rowActionsOverflowAfter);
                                                    const overflow = visible.slice(rowActionsOverflowAfter);
                                                    return (
                                                        <>
                                                            {inline.map((action) =>
                                                                renderRowActionButton(action, row, baseUrl, runRowAction),
                                                            )}
                                                            {overflow.length > 0 && (
                                                                <DropdownMenu>
                                                                    <DropdownMenuTrigger asChild>
                                                                        <Button
                                                                            type="button"
                                                                            size="sm"
                                                                            variant="ghost"
                                                                            className="size-8 p-0"
                                                                            aria-label={__('More actions')}
                                                                        >
                                                                            <MoreHorizontal className="size-4" />
                                                                        </Button>
                                                                    </DropdownMenuTrigger>
                                                                    <DropdownMenuContent>
                                                                        {overflow.map((action) =>
                                                                            renderRowActionMenuItem(action, row, baseUrl, runRowAction),
                                                                        )}
                                                                    </DropdownMenuContent>
                                                                </DropdownMenu>
                                                            )}
                                                        </>
                                                    );
                                                })()}
                                            </div>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>

                <TableFooter
                    pagination={pagination}
                    perPage={filters.per_page}
                    perPageOptions={perPageOptions}
                    style={schema.pagination_style ?? 'numbered'}
                    onNavigate={navigate}
                    __={__}
                />
            </Card>

            {layoutKind === 'below_content' && filtersBlock}

            <ConfirmDialog
                open={confirmAction !== null}
                title={confirmAction?.action.label ?? __('Confirm')}
                description={
                    confirmAction?.kind === 'bulk'
                        ? __('Delete :count selected record(s)? This cannot be undone.', { count: selected.size })
                        : __('Delete this record? This cannot be undone.')
                }
                confirmLabel={confirmAction?.action.label ?? __('Confirm')}
                destructive={confirmAction?.action.destructive ?? true}
                onCancel={() => setConfirmAction(null)}
                onConfirm={() => {
                    if (!confirmAction) return;
                    if (confirmAction.kind === 'bulk') {
                        submitBulkAction(confirmAction.action);
                    } else if (confirmAction.row) {
                        submitRowAction(confirmAction.action, confirmAction.row);
                    }
                    setConfirmAction(null);
                }}
            />
        </div>
    );
}

function renderCell(col: Column, value: unknown, __: (key: string) => string = (key) => key) {
    if (value === null || value === undefined) {
        return <span className="text-muted-foreground">—</span>;
    }

    if (col.type === 'boolean') {
        const truthy = Boolean(value) && value !== '0' && value !== 0;
        const token = String(truthy ? col.extra.true_color : col.extra.false_color);
        const colorClass =
            badgeColorClasses[token]?.match(/text-\S+/)?.[0] ?? 'text-muted-foreground';
        const iconName = String(truthy ? col.extra.true_icon : col.extra.false_icon);
        const Icon = iconName === 'x' ? X : Check;
        return <Icon className={`size-4 ${colorClass}`} aria-label={truthy ? __('Yes') : __('No')} />;
    }

    if (col.type === 'icon') {
        const key = String(value);
        const iconMap = (col.extra.icons ?? {}) as Record<string, string>;
        const colorMap = (col.extra.colors ?? {}) as Record<string, string>;
        const name = iconMap[key] ?? (col.extra.icon as string | null | undefined) ?? null;
        if (!name) {
            return <span className="text-muted-foreground">—</span>;
        }
        const token = colorMap[key] ?? (col.extra.color as string | null | undefined) ?? null;
        const colorClass = token
            ? (badgeColorClasses[token]?.match(/text-\S+/)?.[0] ?? 'text-muted-foreground')
            : 'text-foreground';
        const size = Number(col.extra.size ?? 20);
        return (
            <DynamicIcon
                name={name as IconName}
                size={size}
                className={colorClass}
                aria-label={key}
            />
        );
    }

    if (col.type === 'image') {
        const size = Number(col.extra.size ?? 40);
        const circular = Boolean(col.extra.circular);
        const src = String(value);
        return (
            <img
                src={src}
                alt=""
                loading="lazy"
                width={size}
                height={size}
                style={{ width: size, height: size, objectFit: 'cover' }}
                className={circular ? 'rounded-full' : 'rounded-md'}
            />
        );
    }

    if (col.type === 'badge') {
        const colors = (col.extra.colors ?? {}) as Record<string, string>;
        const color = colors[String(value)];
        if (color) {
            if (color.startsWith('#')) {
                return (
                    <Badge style={{ backgroundColor: color, color: '#fff', borderColor: 'transparent' }}>
                        {String(value)}
                    </Badge>
                );
            }
            const classes = badgeColorClasses[color] ?? badgeColorClasses.slate;
            return <Badge className={classes}>{String(value)}</Badge>;
        }
        return <Badge variant="secondary">{String(value)}</Badge>;
    }

    const copyable = Boolean(col.extra.copyable);
    const isMarkdown = Boolean(col.extra.markdown);
    const text = String(value);

    if (isMarkdown) {
        return (
            <div
                className="prose prose-sm max-w-none dark:prose-invert"
                dangerouslySetInnerHTML={{ __html: text }}
            />
        );
    }

    if (copyable) {
        return (
            <span className="inline-flex items-center gap-1">
                <span>{text}</span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-xs"
                    className="text-muted-foreground"
                    aria-label={`Copy ${col.label}`}
                    onClick={() => void navigator.clipboard.writeText(text)}
                >
                    <Copy className="size-3" />
                </Button>
            </span>
        );
    }

    return <span>{text}</span>;
}

function iconFor(name: string | null | undefined) {
    if (name === 'pencil') return Pencil;
    if (name === 'eye') return Eye;
    if (name === 'trash-2') return Trash2;
    return MoreHorizontal;
}

function renderRowActionButton(
    action: RowActionSchema,
    row: Row,
    baseUrl: string,
    run: (action: RowActionSchema, row: Row) => void,
) {
    const Icon = iconFor(action.icon);
    if (action.link && action.route_suffix) {
        return (
            <Button
                key={action.name}
                asChild
                size="sm"
                variant="ghost"
                className="size-8 p-0"
            >
                <Link
                    href={`${baseUrl}/${String(row._key)}/${action.route_suffix}`}
                    aria-label={action.label}
                >
                    <Icon className="size-4" />
                </Link>
            </Button>
        );
    }
    return (
        <Button
            key={action.name}
            type="button"
            size="sm"
            variant="ghost"
            className="size-8 p-0"
            aria-label={action.label}
            onClick={() => run(action, row)}
        >
            <Icon className="size-4" />
        </Button>
    );
}

function TableFooter({
    pagination,
    perPage,
    perPageOptions,
    style,
    onNavigate,
    __,
}: {
    pagination: Pagination;
    perPage: number;
    perPageOptions: number[];
    style: PaginationStyle;
    onNavigate: (patch: Record<string, unknown> & { page?: number }) => void;
    __: (key: string, replacements?: Record<string, string | number>) => string;
}) {
    const current = pagination.current_page;
    const last = Math.max(1, pagination.last_page);
    const goto = (page: number) => {
        const clamped = Math.max(1, Math.min(last, page));
        if (clamped !== current) onNavigate({ page: clamped });
    };

    return (
        <div className="flex flex-col gap-3 border-t px-4 py-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
                <span className="whitespace-nowrap">
                    {__('Showing :from–:to of :total', {
                        from: pagination.from ?? 0,
                        to: pagination.to ?? 0,
                        total: pagination.total,
                    })}
                </span>
                <div className="flex items-center gap-2">
                    <span className="text-xs">{__('Per page')}</span>
                    <Select
                        value={String(perPage)}
                        onValueChange={(v) => onNavigate({ per_page: Number(v) })}
                    >
                        <SelectTrigger className="h-8 w-[80px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {perPageOptions.map((n) => (
                                <SelectItem key={n} value={String(n)}>
                                    {n}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div className="flex items-center gap-2">
                {style === 'simple' && (
                    <SimplePager current={current} last={last} onGoto={goto} __={__} />
                )}
                {style === 'compact' && (
                    <CompactPager current={current} last={last} onGoto={goto} __={__} />
                )}
                {style === 'numbered' && (
                    <NumberedPager current={current} last={last} onGoto={goto} __={__} />
                )}
            </div>
        </div>
    );
}

function SimplePager({
    current,
    last,
    onGoto,
    __,
}: {
    current: number;
    last: number;
    onGoto: (page: number) => void;
    __: (key: string) => string;
}) {
    return (
        <>
            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={current <= 1}
                onClick={() => onGoto(current - 1)}
            >
                {__('Previous')}
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={current >= last}
                onClick={() => onGoto(current + 1)}
            >
                {__('Next')}
            </Button>
        </>
    );
}

function CompactPager({
    current,
    last,
    onGoto,
    __,
}: {
    current: number;
    last: number;
    onGoto: (page: number) => void;
    __: (key: string, replacements?: Record<string, string | number>) => string;
}) {
    return (
        <>
            <Button
                type="button"
                variant="outline"
                size="icon-xs"
                className="size-8"
                disabled={current <= 1}
                onClick={() => onGoto(1)}
                aria-label={__('First page')}
            >
                <ChevronsLeft className="size-4" />
            </Button>
            <Button
                type="button"
                variant="outline"
                size="icon-xs"
                className="size-8"
                disabled={current <= 1}
                onClick={() => onGoto(current - 1)}
                aria-label={__('Previous page')}
            >
                <ChevronLeft className="size-4" />
            </Button>
            <span className="px-2 tabular-nums text-foreground">
                {__('Page :current of :last', { current, last })}
            </span>
            <Button
                type="button"
                variant="outline"
                size="icon-xs"
                className="size-8"
                disabled={current >= last}
                onClick={() => onGoto(current + 1)}
                aria-label={__('Next page')}
            >
                <ChevronRight className="size-4" />
            </Button>
            <Button
                type="button"
                variant="outline"
                size="icon-xs"
                className="size-8"
                disabled={current >= last}
                onClick={() => onGoto(last)}
                aria-label={__('Last page')}
            >
                <ChevronsRight className="size-4" />
            </Button>
        </>
    );
}

function NumberedPager({
    current,
    last,
    onGoto,
    __,
}: {
    current: number;
    last: number;
    onGoto: (page: number) => void;
    __: (key: string, replacements?: Record<string, string | number>) => string;
}) {
    const [jump, setJump] = useState('');
    const pages = pageWindow(current, last);

    return (
        <>
            <Button
                type="button"
                variant="outline"
                size="icon-xs"
                className="size-8"
                disabled={current <= 1}
                onClick={() => onGoto(current - 1)}
                aria-label={__('Previous page')}
            >
                <ChevronLeft className="size-4" />
            </Button>

            {pages.map((p, i) =>
                p === '…' ? (
                    <span key={`gap-${i}`} className="px-1 text-muted-foreground">
                        …
                    </span>
                ) : (
                    <Button
                        key={p}
                        type="button"
                        variant={p === current ? 'default' : 'outline'}
                        size="sm"
                        className="h-8 min-w-8 px-2 tabular-nums"
                        onClick={() => onGoto(p)}
                        aria-current={p === current ? 'page' : undefined}
                        aria-label={__('Go to page :page', { page: p })}
                    >
                        {p}
                    </Button>
                ),
            )}

            <Button
                type="button"
                variant="outline"
                size="icon-xs"
                className="size-8"
                disabled={current >= last}
                onClick={() => onGoto(current + 1)}
                aria-label={__('Next page')}
            >
                <ChevronRight className="size-4" />
            </Button>

            {last > 7 && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        const n = parseInt(jump, 10);
                        if (!Number.isNaN(n)) {
                            onGoto(n);
                            setJump('');
                        }
                    }}
                    className="ml-2 flex items-center gap-1"
                >
                    <Input
                        type="number"
                        min={1}
                        max={last}
                        value={jump}
                        onChange={(e) => setJump(e.target.value)}
                        placeholder={__('Go')}
                        className="h-8 w-16"
                        aria-label={__('Jump to page')}
                    />
                </form>
            )}
        </>
    );
}

function pageWindow(current: number, last: number): Array<number | '…'> {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }
    const pages: Array<number | '…'> = [1];
    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);
    if (start > 2) pages.push('…');
    for (let p = start; p <= end; p++) pages.push(p);
    if (end < last - 1) pages.push('…');
    pages.push(last);
    return pages;
}

function renderRowActionMenuItem(
    action: RowActionSchema,
    row: Row,
    baseUrl: string,
    run: (action: RowActionSchema, row: Row) => void,
) {
    const Icon = iconFor(action.icon);
    if (action.link && action.route_suffix) {
        return (
            <DropdownMenuItem key={action.name} asChild destructive={action.destructive}>
                <Link href={`${baseUrl}/${String(row._key)}/${action.route_suffix}`}>
                    <Icon className="size-4" />
                    <span>{action.label}</span>
                </Link>
            </DropdownMenuItem>
        );
    }
    return (
        <DropdownMenuItem
            key={action.name}
            destructive={action.destructive}
            onSelect={() => run(action, row)}
        >
            <Icon className="size-4" />
            <span>{action.label}</span>
        </DropdownMenuItem>
    );
}
