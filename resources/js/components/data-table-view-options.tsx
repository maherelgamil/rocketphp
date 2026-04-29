import { Settings2 } from 'lucide-react';
import { Button } from './ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from './ui/dropdown-menu';

type ColumnOption = {
    name: string;
    label: string;
    visible: boolean;
};

type Props = {
    columns: ColumnOption[];
    onToggle: (name: string, visible: boolean) => void;
    __: (key: string) => string;
};

export default function DataTableViewOptions({ columns, onToggle, __ }: Props) {
    if (columns.length === 0) return null;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button type="button" variant="outline">
                    <Settings2 className="me-2 size-4" />
                    {__('View')}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44">
                <DropdownMenuLabel>{__('Toggle columns')}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {columns.map((col) => (
                    <DropdownMenuCheckboxItem
                        key={col.name}
                        checked={col.visible}
                        onCheckedChange={(checked) => onToggle(col.name, Boolean(checked))}
                        onSelect={(e) => e.preventDefault()}
                    >
                        {col.label}
                    </DropdownMenuCheckboxItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
