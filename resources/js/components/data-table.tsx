import { Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown, Pencil, Search } from 'lucide-react';
import { useState } from 'react';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Card } from './ui/card';
import { Input } from './ui/input';
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

type Pagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type Filters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
    per_page: number;
};

type Props = {
    schema: Schema;
    records: Row[];
    pagination: Pagination;
    filters: Filters;
    baseUrl: string;
    editable?: boolean;
};

export default function DataTable({
    schema,
    records,
    pagination,
    filters,
    baseUrl,
    editable = false,
}: Props) {
    const totalColumns = schema.columns.length + (editable ? 1 : 0);
    const [search, setSearch] = useState(filters.search);

    const navigate = (next: Partial<Filters> & { page?: number }) => {
        router.get(
            baseUrl,
            {
                search: next.search ?? filters.search,
                sort: next.sort ?? filters.sort,
                direction: next.direction ?? filters.direction,
                per_page: next.per_page ?? filters.per_page,
                page: next.page,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const toggleSort = (column: Column) => {
        if (!column.sortable) return;
        const nextDirection: 'asc' | 'desc' =
            filters.sort === column.name && filters.direction === 'asc' ? 'desc' : 'asc';
        navigate({ sort: column.name, direction: nextDirection, page: 1 });
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

    return (
        <div className="space-y-4">
            {schema.searchable && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        navigate({ search, page: 1 });
                    }}
                    className="flex max-w-sm items-center gap-2"
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
                </form>
            )}

            <Card className="overflow-hidden p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
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
                            {editable && <TableHead className="w-16 text-right">Actions</TableHead>}
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
                                    {schema.columns.map((col) => (
                                        <TableCell key={col.name}>
                                            {renderCell(col, row[col.name])}
                                        </TableCell>
                                    ))}
                                    {editable && (
                                        <TableCell className="text-right">
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
            return (
                <Badge style={{ backgroundColor: color, color: '#fff', borderColor: 'transparent' }}>
                    {String(value)}
                </Badge>
            );
        }
        return <Badge variant="secondary">{String(value)}</Badge>;
    }

    return <span>{String(value)}</span>;
}
