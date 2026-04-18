import { Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Copy,
    MoreHorizontal,
    Pencil,
    Search,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import ConfirmDialog from './confirm-dialog';
import { DateRangePicker } from './date-range-picker';
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

type Schema = {
    columns: Column[];
    searchable: boolean;
    default_sort: string | null;
    default_sort_direction: 'asc' | 'desc';
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
    bulkActions?: RowActionSchema[];
    tableFilters?: TableFilterSchema[];
    perPageOptions?: number[];
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
    bulkActions = [],
    tableFilters = [],
    perPageOptions = [10, 25, 50, 100],
}: Props) {
    const hasBulk = bulkActions.length > 0;
    const [selected, setSelected] = useState<Set<string>>(() => new Set());
    const [search, setSearch] = useState(filters.search);
    const [confirmAction, setConfirmAction] = useState<{
        kind: 'row' | 'bulk';
        action: RowActionSchema;
        row?: Row;
    } | null>(null);

    const navigate = (patch: Record<string, unknown> & { page?: number }) => {
        const next: Record<string, unknown> = { ...query, ...patch };
        if (patch.page === undefined) {
            next.page = 1;
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
            return <ArrowUpDown className="ml-2 size-3.5 opacity-40" />;
        }
        return filters.direction === 'asc' ? (
            <ArrowUp className="ml-2 size-3.5" />
        ) : (
            <ArrowDown className="ml-2 size-3.5" />
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
                                            <SelectValue placeholder="All" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">All</SelectItem>
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
                                            <SelectItem value="all">All</SelectItem>
                                            <SelectItem value="yes">Yes</SelectItem>
                                            <SelectItem value="no">No</SelectItem>
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
                            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Search..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button type="submit" size="sm" variant="secondary">
                            Search
                        </Button>
                    </form>
                )}

                <div className="ml-auto flex items-center gap-2">
                    <span className="text-xs text-muted-foreground">Per page</span>
                    <Select
                        value={String(filters.per_page)}
                        onValueChange={(v) => navigate({ per_page: Number(v) })}
                    >
                        <SelectTrigger className="h-9 w-[88px]">
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
                                        aria-label="Select all"
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
                                <TableHead className="w-24 text-right">Actions</TableHead>
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
                                    No records found.
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
                                                aria-label="Select row"
                                                className="size-4 rounded border"
                                            />
                                        </TableCell>
                                    )}
                                    {schema.columns.map((col) => (
                                        <TableCell key={col.name}>
                                            {renderCell(col, row[col.name])}
                                        </TableCell>
                                    ))}
                                    {(editable || rowActions.length > 0) && (
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                {editable && Boolean(row._can_update) && (
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="ghost"
                                                        className="size-8 p-0"
                                                    >
                                                        <Link
                                                            href={`${baseUrl}/${String(row._key)}/edit`}
                                                            aria-label="Edit"
                                                        >
                                                            <Pencil className="size-4" />
                                                        </Link>
                                                    </Button>
                                                )}
                                                {rowActions
                                                    .filter((a) => a.scope === 'row')
                                                    .map((action) => {
                                                        if (action.name === 'delete' && !row._can_delete) {
                                                            return null;
                                                        }
                                                        const Icon =
                                                            action.icon === 'trash-2' ? Trash2 : MoreHorizontal;
                                                        return (
                                                            <Button
                                                                key={action.name}
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                className="size-8 p-0"
                                                                aria-label={action.label}
                                                                onClick={() => runRowAction(action, row)}
                                                            >
                                                                <Icon className="size-4" />
                                                            </Button>
                                                        );
                                                    })}
                                            </div>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>

                <div className="flex items-center justify-between border-t px-4 py-3 text-sm text-muted-foreground">
                    <span>
                        {pagination.from ?? 0}–{pagination.to ?? 0} of {pagination.total}
                    </span>
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={pagination.current_page <= 1}
                            onClick={() => navigate({ page: pagination.current_page - 1 })}
                        >
                            Previous
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={pagination.current_page >= pagination.last_page}
                            onClick={() => navigate({ page: pagination.current_page + 1 })}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </Card>

            <ConfirmDialog
                open={confirmAction !== null}
                title={confirmAction?.action.label ?? 'Confirm'}
                description={
                    confirmAction?.kind === 'bulk'
                        ? `Delete ${selected.size} selected record(s)? This cannot be undone.`
                        : 'Delete this record? This cannot be undone.'
                }
                confirmLabel={confirmAction?.action.label ?? 'Confirm'}
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

function renderCell(col: Column, value: unknown) {
    if (value === null || value === undefined) {
        return <span className="text-muted-foreground">—</span>;
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
    const text = String(value);

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
