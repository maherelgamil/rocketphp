import { Link, useForm } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import { cn } from '../lib/utils';
import FormField, { type FieldSchema } from './form-field';
import { Button } from './ui/button';
import { Card } from './ui/card';

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

type Action = {
    method: 'post' | 'patch' | 'put';
    url: string;
};

type Props = {
    form: Schema;
    state: Record<string, unknown>;
    action: Action;
    indexUrl: string;
    submitLabel: string;
};

function gridClassFor(columns: number): string {
    const n = Math.max(1, Math.min(columns ?? 1, 4));
    return cn(
        'grid gap-6',
        n === 1 && 'grid-cols-1',
        n === 2 && 'grid-cols-1 md:grid-cols-2',
        n === 3 && 'grid-cols-1 md:grid-cols-3',
        n === 4 && 'grid-cols-1 md:grid-cols-4',
    );
}

function isSection(node: Node): node is SectionSchema {
    return (node as SectionSchema).type === 'section';
}

export default function RecordForm({ form, state, action, indexUrl, submitLabel }: Props) {
    const inertia = useForm<Record<string, unknown>>(state);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const method = action.method;
        inertia[method](action.url, { preserveScroll: true });
    };

    const renderField = (field: FieldSchema) => (
        <FormField
            key={field.name}
            field={field}
            value={inertia.data[field.name]}
            error={inertia.errors[field.name as keyof typeof inertia.errors] as string | undefined}
            onChange={(v) => inertia.setData(field.name, v)}
        />
    );

    const hasSections = form.fields.some(isSection);

    return (
        <form onSubmit={submit} className="space-y-6">
            {hasSections ? (
                form.fields.map((node, idx) =>
                    isSection(node) ? (
                        <FormSection key={`${node.label}-${idx}`} section={node}>
                            <div className={gridClassFor(node.columns)}>
                                {node.fields.map(renderField)}
                            </div>
                        </FormSection>
                    ) : (
                        <Card key={node.name} className="p-6">
                            <div className={gridClassFor(form.columns)}>{renderField(node)}</div>
                        </Card>
                    ),
                )
            ) : (
                <Card className="p-6">
                    <div className={gridClassFor(form.columns)}>
                        {(form.fields as FieldSchema[]).map(renderField)}
                    </div>
                </Card>
            )}

            <div className="flex items-center justify-end gap-2">
                <Button type="button" variant="ghost" asChild>
                    <Link href={indexUrl}>Cancel</Link>
                </Button>
                <Button type="submit" disabled={inertia.processing}>
                    {inertia.processing ? 'Saving...' : submitLabel}
                </Button>
            </div>
        </form>
    );
}

function FormSection({
    section,
    children,
}: {
    section: SectionSchema;
    children: React.ReactNode;
}) {
    const [open, setOpen] = useState(!section.collapsed);
    const canToggle = section.collapsible;

    return (
        <Card className="overflow-hidden p-0">
            <div className="flex items-start justify-between border-b px-6 py-4">
                <div>
                    <h3 className="text-base font-semibold">{section.label}</h3>
                    {section.description && (
                        <p className="mt-1 text-sm text-muted-foreground">{section.description}</p>
                    )}
                </div>
                {canToggle && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="size-8 p-0"
                        onClick={() => setOpen((v) => !v)}
                        aria-label={open ? 'Collapse' : 'Expand'}
                    >
                        <ChevronDown
                            className={cn('size-4 transition-transform', !open && '-rotate-90')}
                        />
                    </Button>
                )}
            </div>
            {open && <div className="p-6">{children}</div>}
        </Card>
    );
}
