import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from './ui/table';

type TableColumns = { name: string; label: string }[];
type TableRows = Record<string, unknown>[];

function renderTable(columns: TableColumns, rows: TableRows, emptyMessage = 'No records'): React.ReactNode {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    {columns.map((c) => (
                        <TableHead key={c.name}>{c.label}</TableHead>
                    ))}
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.length === 0 ? (
                    <TableRow>
                        <TableCell colSpan={columns.length} className="text-center text-muted-foreground">
                            {emptyMessage}
                        </TableCell>
                    </TableRow>
                ) : (
                    rows.map((row, ri) => (
                        <TableRow key={ri}>
                            {columns.map((c) => (
                                <TableCell key={c.name}>{String(row[c.name] ?? '—')}</TableCell>
                            ))}
                        </TableRow>
                    ))
                )}
            </TableBody>
        </Table>
    );
}

export function TableBlockRenderer({
    columns,
    rows,
    emptyMessage = 'No records',
}: {
    columns: TableColumns;
    rows: TableRows;
    emptyMessage?: string;
}) {
    return <div className="-mx-6 -mt-6">{renderTable(columns, rows, emptyMessage)}</div>;
}

export { renderTable };
