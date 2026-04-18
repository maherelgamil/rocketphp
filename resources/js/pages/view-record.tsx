import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import PanelShell from '../components/panel-shell';
import type { FieldSchema } from '../components/form-field';
import { cn } from '../lib/utils';
import { Button } from '../components/ui/button';
import { Card } from '../components/ui/card';

type SectionSchema = {
    type: 'section';
    label: string;
    description: string | null;
    columns: number;
    collapsible: boolean;
    collapsed: boolean;
    fields: FieldSchema[];
};

type Node = FieldSchema | SectionSchema;

type Schema = {
    columns: number;
    fields: Node[];
};

type PanelProp = {
    id: string;
    brand: string;
    path: string;
    navigation: {
        label: string;
        url: string;
        group?: string | null;
        sort?: number;
        icon?: string | null;
    }[];
};

type Props = {
    panel: PanelProp;
    resource: {
        slug: string;
        label: string;
        pluralLabel: string;
    };
    form: Schema;
    state: Record<string, unknown>;
    edit_url: string | null;
    index_url: string;
};

function isSection(node: Node): node is SectionSchema {
    return (node as SectionSchema).type === 'section';
}

function gridFor(columns: number): string {
    const n = Math.max(1, Math.min(columns ?? 1, 4));
    return cn(
        'grid gap-6',
        n === 1 && 'grid-cols-1',
        n === 2 && 'grid-cols-1 md:grid-cols-2',
        n === 3 && 'grid-cols-1 md:grid-cols-3',
        n === 4 && 'grid-cols-1 md:grid-cols-4',
    );
}

function formatValue(field: FieldSchema, value: unknown): React.ReactNode {
    if (value === null || value === undefined || value === '') {
        return <span className="text-muted-foreground">—</span>;
    }

    if (field.type === 'toggle' || field.type === 'checkbox') {
        return <span>{value ? 'Yes' : 'No'}</span>;
    }

    if (field.type === 'select' || field.type === 'radio') {
        const options = (field.extra.options ?? {}) as Record<string, string>;
        return <span>{options[String(value)] ?? String(value)}</span>;
    }

    if (field.type === 'multi_select') {
        const options = (field.extra.options ?? {}) as Record<string, string>;
        const arr = Array.isArray(value) ? value.map(String) : [];
        if (arr.length === 0) return <span className="text-muted-foreground">—</span>;
        return <span>{arr.map((v) => options[v] ?? v).join(', ')}</span>;
    }

    if (field.type === 'file') {
        const str = String(value);
        return <span className="break-all font-mono text-xs">{str}</span>;
    }

    if (field.type === 'textarea') {
        return <p className="whitespace-pre-wrap">{String(value)}</p>;
    }

    return <span>{String(value)}</span>;
}

function FieldView({ field, value }: { field: FieldSchema; value: unknown }) {
    return (
        <div className="space-y-1">
            <dt className="text-xs font-medium text-muted-foreground">{field.label}</dt>
            <dd className="text-sm">{formatValue(field, value)}</dd>
        </div>
    );
}

export default function ViewRecord({
    panel,
    resource,
    form,
    state,
    edit_url,
    index_url,
}: Props) {
    const renderField = (field: FieldSchema) => (
        <FieldView key={field.name} field={field} value={state[field.name]} />
    );

    const hasSections = form.fields.some(isSection);

    return (
        <PanelShell panel={panel}>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">View {resource.label}</h1>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" asChild>
                            <Link href={index_url}>Back</Link>
                        </Button>
                        {edit_url && (
                            <Button asChild>
                                <Link href={edit_url}>
                                    <Pencil className="mr-2 size-4" />
                                    Edit
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {hasSections ? (
                    form.fields.map((node, idx) =>
                        isSection(node) ? (
                            <Card key={`${node.label}-${idx}`} className="overflow-hidden p-0">
                                <div className="border-b px-6 py-4">
                                    <h3 className="text-base font-semibold">{node.label}</h3>
                                    {node.description && (
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {node.description}
                                        </p>
                                    )}
                                </div>
                                <dl className={cn(gridFor(node.columns), 'p-6')}>
                                    {node.fields.map(renderField)}
                                </dl>
                            </Card>
                        ) : (
                            <Card key={node.name} className="p-6">
                                <dl className={gridFor(form.columns)}>{renderField(node)}</dl>
                            </Card>
                        ),
                    )
                ) : (
                    <Card className="p-6">
                        <dl className={gridFor(form.columns)}>
                            {(form.fields as FieldSchema[]).map(renderField)}
                        </dl>
                    </Card>
                )}
            </div>
        </PanelShell>
    );
}
