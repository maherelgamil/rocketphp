import { Link, useForm } from '@inertiajs/react';
import { Button } from './ui/button';
import { Card } from './ui/card';
import FormField, { type FieldSchema } from './form-field';
import { cn } from '../lib/utils';

type Schema = {
    columns: number;
    fields: FieldSchema[];
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

export default function RecordForm({ form, state, action, indexUrl, submitLabel }: Props) {
    const inertia = useForm<Record<string, unknown>>(state);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const method = action.method;
        inertia[method](action.url, {
            preserveScroll: true,
        });
    };

    const columns = Math.max(1, Math.min(form.columns ?? 1, 4));
    const gridClass = cn(
        'grid gap-6',
        columns === 1 && 'grid-cols-1',
        columns === 2 && 'grid-cols-1 md:grid-cols-2',
        columns === 3 && 'grid-cols-1 md:grid-cols-3',
        columns === 4 && 'grid-cols-1 md:grid-cols-4',
    );

    return (
        <form onSubmit={submit} className="space-y-6">
            <Card className="p-6">
                <div className={gridClass}>
                    {form.fields.map((field) => (
                        <FormField
                            key={field.name}
                            field={field}
                            value={inertia.data[field.name]}
                            error={inertia.errors[field.name as keyof typeof inertia.errors] as string | undefined}
                            onChange={(v) => inertia.setData(field.name, v)}
                        />
                    ))}
                </div>
            </Card>

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
