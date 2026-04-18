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

export default function EditRecord({ panel, resource, form, state, action, index_url }: Props) {
    return (
        <PanelShell panel={panel} activeSlug={resource.slug}>
            <Head title={`Edit ${resource.label}`} />
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight">Edit {resource.label}</h1>
            </div>
            <RecordForm
                form={form}
                state={state}
                action={action}
                indexUrl={index_url}
                submitLabel="Save changes"
            />
        </PanelShell>
    );
}
