import { useForm } from '@inertiajs/react';
import AuthLayout, { type AuthPanelProp } from '../components/auth-layout';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';

type Props = {
    panel: AuthPanelProp;
    token: string;
    email: string;
};

export default function ResetPassword({ panel, token, email }: Props) {
    const form = useForm({
        token,
        email: email ?? '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/${panel.path}/reset-password`);
    };

    return (
        <AuthLayout panel={panel} title="Reset your password">
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                    />
                    {form.errors.email ? (
                        <p className="mt-1 text-xs text-destructive">{form.errors.email}</p>
                    ) : null}
                </div>
                <div>
                    <Label htmlFor="password">New password</Label>
                    <Input
                        id="password"
                        type="password"
                        autoComplete="new-password"
                        required
                        value={form.data.password}
                        onChange={(e) => form.setData('password', e.target.value)}
                    />
                    {form.errors.password ? (
                        <p className="mt-1 text-xs text-destructive">{form.errors.password}</p>
                    ) : null}
                </div>
                <div>
                    <Label htmlFor="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        required
                        value={form.data.password_confirmation}
                        onChange={(e) => form.setData('password_confirmation', e.target.value)}
                    />
                </div>
                <Button type="submit" className="w-full" disabled={form.processing}>
                    {form.processing ? 'Resetting…' : 'Reset password'}
                </Button>
            </form>
        </AuthLayout>
    );
}
