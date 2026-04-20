import { Head } from '@inertiajs/react';
import PanelShell from '../components/panel-shell';
import RecordForm from '../components/record-form';
import { create__ } from '../lib/i18n';

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    resource: { slug: string; label: string; pluralLabel: string };
    form: Parameters<typeof RecordForm>[0]['form'];
    state: Record<string, unknown>;
    action: Parameters<typeof RecordForm>[0]['action'];
    index_url: string;
};

export default function CreateRecord({ panel, resource, form, state, action, index_url }: Props) {
    const __ = create__(panel.translations ?? {});

    return (
        <PanelShell panel={panel} activeSlug={resource.slug}>
            <Head title={__('Create :resource', { resource: resource.label })} />
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {__('Create :resource', { resource: resource.label })}
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    {__('Add a new :resource to :resources.', {
                        resource: resource.label.toLowerCase(),
                        resources: resource.pluralLabel.toLowerCase(),
                    })}
                </p>
            </div>
            <RecordForm
                form={form}
                state={state}
                action={action}
                indexUrl={index_url}
                submitLabel={__('Create')}
                __={__}
            />
        </PanelShell>
    );
}
