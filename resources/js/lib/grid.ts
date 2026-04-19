const GRID_CLASSES: Record<number, string> = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 md:grid-cols-2',
    3: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    6: 'grid-cols-2 md:grid-cols-3 lg:grid-cols-6',
};

const COL_SPAN_CLASSES: Record<number | string, string> = {
    1: 'col-span-1',
    2: 'col-span-2',
    3: 'col-span-3',
    4: 'col-span-4',
    5: 'col-span-5',
    6: 'col-span-6',
    full: 'col-span-full',
};

export function gridClass(columns: number): string {
    return GRID_CLASSES[columns] ?? 'grid-cols-1';
}

export function colSpanClass(span: number | string = 1): string {
    return COL_SPAN_CLASSES[span] ?? 'col-span-1';
}
