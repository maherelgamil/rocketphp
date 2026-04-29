import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import CreateRecord from './pages/create-record';
import Dashboard from './pages/dashboard';
import EditRecord from './pages/edit-record';
import ErrorPage from './pages/error';
import ForgotPassword from './pages/forgot-password';
import ListRecords from './pages/list-records';
import Login from './pages/login';
import Notifications from './pages/notifications';
import RocketPage from './pages/page';
import Profile from './pages/profile';
import Register from './pages/register';
import ResetPassword from './pages/reset-password';
import VerifyEmail from './pages/verify-email';
import ViewRecord from './pages/view-record';

const pages: Record<string, () => Promise<unknown> | unknown> = {
    'rocket/list-records': () => ({ default: ListRecords }),
    'rocket/create-record': () => ({ default: CreateRecord }),
    'rocket/edit-record': () => ({ default: EditRecord }),
    'rocket/view-record': () => ({ default: ViewRecord }),
    'rocket/dashboard': () => ({ default: Dashboard }),
    'rocket/notifications': () => ({ default: Notifications }),
    'rocket/error': () => ({ default: ErrorPage }),
    'rocket/page': () => ({ default: RocketPage }),
    'rocket/login': () => ({ default: Login }),
    'rocket/register': () => ({ default: Register }),
    'rocket/forgot-password': () => ({ default: ForgotPassword }),
    'rocket/reset-password': () => ({ default: ResetPassword }),
    'rocket/verify-email': () => ({ default: VerifyEmail }),
    'rocket/profile': () => ({ default: Profile }),
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
