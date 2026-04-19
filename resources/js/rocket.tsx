import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import CreateRecord from './pages/create-record';
import Dashboard from './pages/dashboard';
import EditRecord from './pages/edit-record';
import ErrorPage from './pages/error';
import ListRecords from './pages/list-records';
import Notifications from './pages/notifications';
import ViewRecord from './pages/view-record';
import RocketPage from './pages/page';

const pages: Record<string, () => Promise<unknown> | unknown> = {
    'rocket/ListRecords': () => ({ default: ListRecords }),
    'rocket/CreateRecord': () => ({ default: CreateRecord }),
    'rocket/EditRecord': () => ({ default: EditRecord }),
    'rocket/ViewRecord': () => ({ default: ViewRecord }),
    'rocket/Dashboard': () => ({ default: Dashboard }),
    'rocket/Notifications': () => ({ default: Notifications }),
    'rocket/Error': () => ({ default: ErrorPage }),
    'rocket/Page': () => ({ default: RocketPage }),
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
