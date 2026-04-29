import { Link, useForm, usePage } from '@inertiajs/react';
import AuthLayout, { type AuthPanelProp } from '../components/auth-layout';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';

type Props = { panel: AuthPanelProp };

export default function ForgotPassword({ panel }: Props) {
    const flash = (usePage().props as { flash?: { status?: string } }).flash ?? {};
    const form = useForm({ email: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/${panel.path}/forgot-password`);
    };

    return (
        <AuthLayout
            panel={panel}
            title="Forgot password?"
            description="Enter your email and we'll send you a reset link."
            footer={
                panel.auth?.login_url ? (
                    <Link href={panel.auth.login_url} className="text-foreground underline">
                        Back to sign in
                    </Link>
                ) : null
            }
        >
            {flash.status ? (
                <p className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                    {flash.status}
                </p>
            ) : null}
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="email"
                        required
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                    />
                    {form.errors.email ? (
                        <p className="mt-1 text-xs text-destructive">{form.errors.email}</p>
                    ) : null}
                </div>
                <Button type="submit" className="w-full" disabled={form.processing}>
                    {form.processing ? 'Sending…' : 'Send reset link'}
                </Button>
            </form>
        </AuthLayout>
    );
}
