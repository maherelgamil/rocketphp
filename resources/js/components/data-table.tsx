import { Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
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
import { useMemo, useState } from 'react';
import ConfirmDialog from './confirm-dialog';
import { DateRangePicker } from './date-range-picker';
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
    extra: Record<string, unknown>;
};

type PaginationStyle = 'simple' | 'numbered' | 'compact';

type Schema = {
    columns: Column[];
    searchable: boolean;
    default_sort: string | null;
    default_sort_direction: 'asc' | 'desc';
    pagination_style?: PaginationStyle;
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

type TableFilterSchema =
    | {
          type: 'select';
          name: string;
          query_key: string;
          label: string;
          options: Record<string, string>;
          value: string | string[] | null | undefined;
      }
    | {
          type: 'ternary';
          name: string;
          query_key: string;
          label: string;
          value: string | string[] | null | undefined;
      }
    | {
          type: 'date_range';
          name: string;
          label: string;
          from_key: string;
          until_key: string;
          from: string | string[] | null | undefined;
          until: string | string[] | null | undefined;
      }
    | {
          type: 'trashed';
          name: string;
          query_key: string;
          label: string;
          options: Record<string, string>;
          value: string | string[] | null | undefined;
      };

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
    __ = (key) => key,
}: Props) {
    const prefixKey = (k: string) => (paramPrefix ? paramPrefix + k : k);
    const coreKeys = new Set(['page', 'search', 'sort', 'direction', 'per_page']);
    const hasBulk = bulkActions.length > 0;
    const [selected, setSelected] = useState<Set<string>>(() => new Set());
    const [search, setSearch] = useState(filters.search);
    const [confirmAction, setConfirmAction] = useState<{
        kind: 'row' | 'bulk';
        action: RowActionSchema;
        row?: Row;
    } | null>(null);

    const navigate = (patch: Record<string, unknown> & { page?: number }) => {
        const next: Record<string, unknown> = { ...query };
        for (const [k, v] of Object.entries(patch)) {
            const key = coreKeys.has(k) ? prefixKey(k) : k;
            next[key] = v;
        }
        if (patch.page === undefined) {
            next[prefixKey('page')] = 1;
        }
        router.get(baseUrl, next, { preserveState: true, preserveScroll: true, replace: true });
    };

    const toggleSort = (column: Column) => {
        if (!column.sortable) return;
        const nextDirection: 'asc' | 'desc' =
            filters.sort === column.name && filters.direction === 'asc' ? 'desc' : 'asc';
        navigate({ sort: column.name, direction: nextDirection });
    };

    const sortIcon = (column: Column) => {
        if (!column.sortable) return null;
        if (filters.sort !== column.name) {
            return <ArrowUpDown className="ms-2 size-3.5 opacity-40" />;
        }
        return filters.direction === 'asc' ? (
            <ArrowUp className="ms-2 size-3.5" />
        ) : (
            <ArrowDown className="ms-2 size-3.5" />
        );
    };

    const selectionColumn = hasBulk ? 1 : 0;
    const actionColumn =
        (editable ? 1 : 0) + (rowActions.filter((a) => a.scope === 'row').length > 0 ? 1 : 0);
    const totalColumns = schema.columns.length + selectionColumn + actionColumn;

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

    return (
        <div className="space-y-4">
            {tableFilters.length > 0 && (
                <div className="flex flex-wrap items-end gap-3">
                    {tableFilters.map((f) => {
                        if (f.type === 'select') {
                            return (
                                <div key={f.name} className="space-y-1">
                                    <label className="text-xs font-medium text-muted-foreground">
                                        {f.label}
                                    </label>
                                    <Select
                                        value={qString(f.value) || '__all__'}
                                        onValueChange={(v) =>
                                            navigate({
                                                [f.query_key]: v === '__all__' ? '' : v,
                                            })
                                        }
                                    >
                                        <SelectTrigger className="h-9 w-[180px]">
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
                            return (
                                <div key={f.name} className="space-y-1">
                                    <label className="text-xs font-medium text-muted-foreground">
                                        {f.label}
                                    </label>
                                    <Select
                                        value={qString(f.value) || 'all'}
                                        onValueChange={(v) => navigate({ [f.query_key]: v === 'all' ? '' : v })}
                                    >
                                        <SelectTrigger className="h-9 w-[160px]">
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
                            return (
                                <div key={f.name} className="space-y-1">
                                    <label className="text-xs font-medium text-muted-foreground">
                                        {f.label}
                                    </label>
                                    <Select
                                        value={qString(f.value) || 'without'}
                                        onValueChange={(v) => navigate({ [f.query_key]: v })}
                                    >
                                        <SelectTrigger className="h-9 w-[200px]">
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
                            return (
                                <DateRangePicker
                                    key={f.name}
                                    label={f.label}
                                    from={qString(f.from)}
                                    until={qString(f.until)}
                                    onChange={(from, until) =>
                                        navigate({ [f.from_key]: from, [f.until_key]: until })
                                    }
                                />
                            );
                        }
                        return null;
                    })}
                </div>
            )}

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
                                className="ps-9"
                            />
                        </div>
                        <Button type="submit" size="sm" variant="secondary">
                            {__('Search')}
                        </Button>
                    </form>
                )}

            </div>

            {hasBulk && bulkDelete && selected.size > 0 && (
                <div className="flex items-center gap-2 rounded-md border border-border bg-muted/40 px-3 py-2 text-sm">
                    <span className="text-muted-foreground">{selected.size} selected</span>
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
                            {schema.columns.map((col) => (
                                <TableHead
                                    key={col.name}
                                    onClick={() => toggleSort(col)}
                                    className={col.sortable ? 'cursor-pointer select-none' : ''}
                                >
                                    <span className="inline-flex items-center">
                                        {col.label}
                                        {sortIcon(col)}
                                    </span>
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
                                    {schema.columns.map((col) => (
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
                        size="icon-xs"
                        className="size-8 tabular-nums"
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
