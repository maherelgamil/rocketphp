import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import CreateRecord from './pages/create-record';
import EditRecord from './pages/edit-record';
import ListRecords from './pages/list-records';

const pages: Record<string, () => Promise<unknown> | unknown> = {
    'rocket/ListRecords': () => ({ default: ListRecords }),
    'rocket/CreateRecord': () => ({ default: CreateRecord }),
    'rocket/EditRecord': () => ({ default: EditRecord }),
};

createInertiaApp({
    resolve: async (name) => {
        const loader = pages[name];
        if (!loader) {
            throw new Error(`Unknown Rocket page: ${name}`);
        }
        const mod = await loader();
        // @ts-expect-error runtime resolved module shape
        return mod.default ? mod : { default: mod };
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
