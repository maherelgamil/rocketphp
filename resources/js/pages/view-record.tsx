import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import PanelShell from '../components/panel-shell';
import type { FieldSchema } from '../components/form-field';
import RelationManagers from '../components/relation-managers';
import { colSpanClass, gridClass } from '../lib/grid';
import { create__ } from '../lib/i18n';
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

type TabsSchema = {
    type: 'tabs';
    tabs: SectionSchema[];
};

type Node = FieldSchema | SectionSchema | TabsSchema;

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
    translations?: Record<string, string>;
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
    relation_managers?: Parameters<typeof RelationManagers>[0]['managers'];
    relation_managers_layout?: Parameters<typeof RelationManagers>[0]['layout'];
    query?: Record<string, unknown>;
};

function isSection(node: Node): node is SectionSchema {
    return (node as SectionSchema).type === 'section';
}

function isTabs(node: Node): node is TabsSchema {
    return (node as TabsSchema).type === 'tabs';
}

function flattenNodes(nodes: Node[]): Node[] {
    const out: Node[] = [];
    for (const node of nodes) {
        if (isTabs(node)) {
            out.push(...node.tabs);
        } else {
            out.push(node);
        }
    }
    return out;
}

function gridFor(columns: number): string {
    return cn('grid gap-6', gridClass(Math.max(1, columns ?? 1)));
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

    if (field.type === 'key_value') {
        const pairs = Array.isArray(value)
            ? (value as { key?: unknown; value?: unknown }[])
                  .map((r) => ({ key: String(r?.key ?? ''), value: String(r?.value ?? '') }))
                  .filter((r) => r.key !== '')
            : [];
        if (pairs.length === 0) return <span className="text-muted-foreground">—</span>;
        return (
            <dl className="space-y-1 text-sm">
                {pairs.map((p, i) => (
                    <div key={`${p.key}-${i}`} className="flex gap-2">
                        <dt className="font-mono text-muted-foreground">{p.key}:</dt>
                        <dd className="font-mono">{p.value}</dd>
                    </div>
                ))}
            </dl>
        );
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
    relation_managers = {},
    relation_managers_layout = 'tabs',
    query = {},
}: Props) {
    const __ = create__(panel.translations ?? {});

    const renderField = (field: FieldSchema) => (
        <div key={field.name} className={colSpanClass(field.column_span ?? 1)}>
            <FieldView field={field} value={state[field.name]} />
        </div>
    );

    const nodes = flattenNodes(form.fields);
    const hasSections = nodes.some(isSection);

    return (
        <PanelShell panel={panel}>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {__('View :resource', { resource: resource.label })}
                        </h1>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" asChild>
                            <Link href={index_url}>{__('Back')}</Link>
                        </Button>
                        {edit_url && (
                            <Button asChild>
                                <Link href={edit_url}>
                                    <Pencil className="me-2 size-4" />
                                    {__('Edit')}
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {hasSections ? (
                    nodes.map((node, idx) =>
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
                        ) : isTabs(node) ? null : (
                            <Card key={(node as FieldSchema).name} className="p-6">
                                <dl className={gridFor(form.columns)}>
                                    {renderField(node as FieldSchema)}
                                </dl>
                            </Card>
                        ),
                    )
                ) : (
                    <Card className="p-6">
                        <dl className={gridFor(form.columns)}>
                            {(nodes as FieldSchema[]).map(renderField)}
                        </dl>
                    </Card>
                )}

                <RelationManagers
                    managers={relation_managers}
                    query={query}
                    layout={relation_managers_layout}
                />
            </div>
        </PanelShell>
    );
}
