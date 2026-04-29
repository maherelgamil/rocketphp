import { ArrowDown, ArrowUp, ChevronsUpDown, EyeOff } from 'lucide-react';
import { Button } from './ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from './ui/dropdown-menu';

type Direction = 'asc' | 'desc';

type Props = {
    label: string;
    name: string;
    sortable: boolean;
    toggleable: boolean;
    activeSort: string | null;
    activeDirection: Direction;
    onSort: (direction: Direction) => void;
    onHide?: () => void;
    __: (key: string) => string;
};

export default function DataTableColumnHeader({
    label,
    name,
    sortable,
    toggleable,
    activeSort,
    activeDirection,
    onSort,
    onHide,
    __,
}: Props) {
    if (!sortable && !toggleable) {
        return <span>{label}</span>;
    }

    const isActive = activeSort === name;
    const Icon = !isActive ? ChevronsUpDown : activeDirection === 'asc' ? ArrowUp : ArrowDown;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="-ms-3 h-8 data-[state=open]:bg-accent"
                >
                    <span>{label}</span>
                    <Icon className="ms-2 size-3.5 opacity-60" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
                {sortable && (
                    <>
                        <DropdownMenuItem onSelect={() => onSort('asc')}>
                            <ArrowUp className="size-3.5 text-muted-foreground" />
                            {__('Asc')}
                        </DropdownMenuItem>
                        <DropdownMenuItem onSelect={() => onSort('desc')}>
                            <ArrowDown className="size-3.5 text-muted-foreground" />
                            {__('Desc')}
                        </DropdownMenuItem>
                    </>
                )}
                {sortable && toggleable && onHide && <DropdownMenuSeparator />}
                {toggleable && onHide && (
                    <DropdownMenuItem onSelect={onHide}>
                        <EyeOff className="size-3.5 text-muted-foreground" />
                        {__('Hide')}
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
