import { Link, useForm } from '@inertiajs/react';
import AuthLayout, { type AuthPanelProp } from '../components/auth-layout';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';

type Props = { panel: AuthPanelProp };

export default function Login({ panel }: Props) {
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/${panel.path}/login`);
    };

    return (
        <AuthLayout
            panel={panel}
            title="Sign in"
            description="Enter your credentials to access the panel."
            footer={
                panel.auth?.register_url ? (
                    <span>
                        New here?{' '}
                        <Link href={panel.auth.register_url} className="text-foreground underline">
                            Create an account
                        </Link>
                    </span>
                ) : null
            }
        >
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
                <div>
                    <div className="flex items-center justify-between">
                        <Label htmlFor="password">Password</Label>
                        {panel.auth?.forgot_password_url ? (
                            <Link
                                href={panel.auth.forgot_password_url}
                                className="text-xs text-muted-foreground underline"
                            >
                                Forgot password?
                            </Link>
                        ) : null}
                    </div>
                    <Input
                        id="password"
                        type="password"
                        autoComplete="current-password"
                        required
                        value={form.data.password}
                        onChange={(e) => form.setData('password', e.target.value)}
                    />
                    {form.errors.password ? (
                        <p className="mt-1 text-xs text-destructive">{form.errors.password}</p>
                    ) : null}
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={form.data.remember}
                        onChange={(e) => form.setData('remember', e.target.checked)}
                    />
                    Remember me
                </label>
                <Button type="submit" className="w-full" disabled={form.processing}>
                    {form.processing ? 'Signing in…' : 'Sign in'}
                </Button>
            </form>
        </AuthLayout>
    );
}
