import { Head } from '@inertiajs/react';
import PanelShell from '../components/panel-shell';
import RecordForm from '../components/record-form';

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    resource: { slug: string; label: string; pluralLabel: string };
    form: Parameters<typeof RecordForm>[0]['form'];
    state: Record<string, unknown>;
    action: Parameters<typeof RecordForm>[0]['action'];
    index_url: string;
};

export default function CreateRecord({ panel, resource, form, state, action, index_url }: Props) {
    return (
        <PanelShell panel={panel} activeSlug={resource.slug}>
            <Head title={`Create ${resource.label}`} />
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight">Create {resource.label}</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Add a new {resource.label.toLowerCase()} to {resource.pluralLabel.toLowerCase()}.
                </p>
            </div>
            <RecordForm
                form={form}
                state={state}
                action={action}
                indexUrl={index_url}
                submitLabel="Create"
            />
        </PanelShell>
    );
}
