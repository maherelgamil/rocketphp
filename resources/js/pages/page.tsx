import { Head, Link, router, usePage } from '@inertiajs/react';
import PanelShell from '../components/panel-shell';
import { Button } from '../components/ui/button';
import { renderContent } from '../components/block-renderer';
import type { Block, PageAction } from '../lib/types';

type PanelProps = { path: string; [key: string]: unknown };

type PageProps = {
    panel: PanelProps;
    page: { title: string; subtitle: string | null; slug: string };
    actions: PageAction[];
    content: Block[];
};

function handleAction(action: PageAction, panelPath: string, pageSlug: string): void {
    if (action.requires_confirmation) {
        const message = action.confirmation_message ?? 'Are you sure?';
        if (!window.confirm(message)) {
            return;
        }
    }
    router.post(`/${panelPath}/pages/${pageSlug}/actions/${action.name}`, {});
}

export default function Page() {
    const { panel, page, actions, content } = usePage<PageProps>().props;

    return (
        <PanelShell panel={panel} activeSlug={page.slug}>
            <Head title={page.title} />

            <div className="mb-6 flex items-start justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{page.title}</h1>
                    {page.subtitle && (
                        <p className="mt-1 text-sm text-muted-foreground">{page.subtitle}</p>
                    )}
                </div>

                {actions.length > 0 && (
                    <div className="flex items-center gap-2">
                        {actions.map((action) => {
                            if (action.url) {
                                return (
                                    <Button key={action.name} asChild variant="outline">
                                        <Link href={action.url}>{action.label}</Link>
                                    </Button>
                                );
                            }

                            return (
                                <Button
                                    key={action.name}
                                    variant="outline"
                                    onClick={() => handleAction(action, panel.path, page.slug)}
                                >
                                    {action.label}
                                </Button>
                            );
                        })}
                    </div>
                )}
            </div>

            <div className="space-y-6">
                {renderContent(content, panel.path, page.slug)}
            </div>
        </PanelShell>
    );
}