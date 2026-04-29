import { useForm } from '@inertiajs/react';
import AuthLayout, { type AuthPanelProp } from '../components/auth-layout';
import { Button } from '../components/ui/button';

type Props = {
    panel: AuthPanelProp;
    status?: string | null;
};

export default function VerifyEmail({ panel, status }: Props) {
    const form = useForm({});

    const resend = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/${panel.path}/verify-email/resend`);
    };

    const logout = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/${panel.path}/logout`);
    };

    return (
        <AuthLayout
            panel={panel}
            title="Verify your email"
            description="We've sent a verification link to your email. Click it to activate your account."
        >
            {status === 'verification-link-sent' ? (
                <p className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                    A new verification link has been sent.
                </p>
            ) : null}
            <div className="space-y-3">
                <form onSubmit={resend}>
                    <Button type="submit" className="w-full" disabled={form.processing}>
                        Resend verification email
                    </Button>
                </form>
                <form onSubmit={logout}>
                    <Button type="submit" variant="ghost" className="w-full">
                        Sign out
                    </Button>
                </form>
            </div>
        </AuthLayout>
    );
}
