import { Link, useForm } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useEffect, useState } from 'react';
import { colSpanClass, gridClass } from '../lib/grid';
import type { Translator } from '../lib/i18n';
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

type TabsSchema = {
    type: 'tabs';
    tabs: SectionSchema[];
};

type Node = FieldSchema | SectionSchema | TabsSchema;

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
    __?: Translator;
};

function gridClassFor(columns: number): string {
    return cn('grid gap-6', gridClass(Math.max(1, columns ?? 1)));
}

function isSection(node: Node): node is SectionSchema {
    return (node as SectionSchema).type === 'section';
}

function isTabs(node: Node): node is TabsSchema {
    return (node as TabsSchema).type === 'tabs';
}

function slugify(label: string): string {
    return label.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
}

export default function RecordForm({ form, state, action, indexUrl, submitLabel, __ = (key) => key }: Props) {
    const inertia = useForm<Record<string, unknown>>(state);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const method = action.method;
        inertia[method](action.url, { preserveScroll: true });
    };

    const renderField = (field: FieldSchema) => (
        <div key={field.name} className={colSpanClass(field.column_span ?? 1)}>
            <FormField
                field={field}
                value={inertia.data[field.name]}
                error={inertia.errors[field.name as keyof typeof inertia.errors] as string | undefined}
                onChange={(v) => inertia.setData(field.name, v)}
                __={__}
            />
        </div>
    );

    const hasLayout = form.fields.some((n) => isSection(n) || isTabs(n));

    const renderSection = (section: SectionSchema, key: string) => (
        <FormSection key={key} section={section} __={__}>
            <div className={gridClassFor(section.columns)}>{section.fields.map(renderField)}</div>
        </FormSection>
    );

    return (
        <form onSubmit={submit} className="space-y-6">
            {hasLayout ? (
                form.fields.map((node, idx) => {
                    if (isTabs(node)) {
                        return (
                            <FormTabs
                                key={`tabs-${idx}`}
                                tabs={node.tabs}
                                errors={inertia.errors as Record<string, string | undefined>}
                                renderField={renderField}
                            />
                        );
                    }
                    if (isSection(node)) {
                        return renderSection(node, `${node.label}-${idx}`);
                    }
                    return (
                        <Card key={node.name} className="p-6">
                            <div className={gridClassFor(form.columns)}>{renderField(node)}</div>
                        </Card>
                    );
                })
            ) : (
                <Card className="p-6">
                    <div className={gridClassFor(form.columns)}>
                        {(form.fields as FieldSchema[]).map(renderField)}
                    </div>
                </Card>
            )}

            <div className="flex items-center justify-end gap-2">
                <Button type="button" variant="ghost" asChild>
                    <Link href={indexUrl}>{__('Cancel')}</Link>
                </Button>
                <Button type="submit" disabled={inertia.processing}>
                    {inertia.processing ? __('Saving...') : submitLabel}
                </Button>
            </div>
        </form>
    );
}

function FormTabs({
    tabs,
    errors,
    renderField,
}: {
    tabs: SectionSchema[];
    errors: Record<string, string | undefined>;
    renderField: (field: FieldSchema) => React.ReactNode;
}) {
    const slugs = tabs.map((t) => slugify(t.label) || 'tab');
    const readHash = () => {
        if (typeof window === 'undefined') return null;
        const match = window.location.hash.match(/tab=([^&]+)/);
        return match ? decodeURIComponent(match[1]) : null;
    };
    const initial = (() => {
        const fromHash = readHash();
        return fromHash && slugs.includes(fromHash) ? fromHash : slugs[0];
    })();
    const [active, setActive] = useState(initial);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        url.hash = `tab=${active}`;
        window.history.replaceState(null, '', url.toString());
    }, [active]);

    const tabHasErrors = (section: SectionSchema): boolean =>
        section.fields.some((f) => Boolean(errors[f.name]));

    return (
        <Card className="overflow-hidden p-0">
            <div role="tablist" className="flex gap-1 border-b px-4 pt-2">
                {tabs.map((tab, idx) => {
                    const slug = slugs[idx];
                    const isActive = slug === active;
                    const hasError = tabHasErrors(tab);
                    return (
                        <button
                            key={slug}
                            type="button"
                            role="tab"
                            aria-selected={isActive}
                            onClick={() => setActive(slug)}
                            className={cn(
                                'inline-flex items-center gap-1.5 border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                                isActive
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {tab.label}
                            {hasError && <span className="size-1.5 rounded-full bg-destructive" aria-hidden />}
                        </button>
                    );
                })}
            </div>
            {tabs.map((tab, idx) => {
                const slug = slugs[idx];
                if (slug !== active) return null;
                return (
                    <div key={slug} className="space-y-2 p-6">
                        {tab.description && (
                            <p className="text-sm text-muted-foreground">{tab.description}</p>
                        )}
                        <div className={gridClassFor(tab.columns)}>{tab.fields.map(renderField)}</div>
                    </div>
                );
            })}
        </Card>
    );
}

function FormSection({
    section,
    children,
    __,
}: {
    section: SectionSchema;
    children: React.ReactNode;
    __: Translator;
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
                        aria-label={open ? __('Collapse') : __('Expand')}
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
