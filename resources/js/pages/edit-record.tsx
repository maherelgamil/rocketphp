import { Head } from '@inertiajs/react';
import PanelShell from '../components/panel-shell';
import RecordForm from '../components/record-form';
import RelationManagers from '../components/relation-managers';
import { create__ } from '../lib/i18n';

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    resource: { slug: string; label: string; pluralLabel: string };
    form: Parameters<typeof RecordForm>[0]['form'];
    state: Record<string, unknown>;
    action: Parameters<typeof RecordForm>[0]['action'];
    index_url: string;
    relation_managers?: Parameters<typeof RelationManagers>[0]['managers'];
    relation_managers_layout?: Parameters<typeof RelationManagers>[0]['layout'];
    query?: Record<string, unknown>;
};

export default function EditRecord({
    panel,
    resource,
    form,
    state,
    action,
    index_url,
    relation_managers = {},
    relation_managers_layout = 'tabs',
    query = {},
}: Props) {
    const __ = create__(panel.translations ?? {});

    return (
        <PanelShell panel={panel} activeSlug={resource.slug}>
            <Head title={__('Edit :resource', { resource: resource.label })} />
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {__('Edit :resource', { resource: resource.label })}
                </h1>
            </div>
            <RecordForm
                form={form}
                state={state}
                action={action}
                indexUrl={index_url}
                submitLabel={__('Save changes')}
                __={__}
            />
            <RelationManagers
                managers={relation_managers}
                query={query}
                layout={relation_managers_layout}
            />
        </PanelShell>
    );
}
