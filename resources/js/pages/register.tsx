import { Link, useForm } from '@inertiajs/react';
import AuthLayout, { type AuthPanelProp } from '../components/auth-layout';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';

type Props = { panel: AuthPanelProp };

export default function Register({ panel }: Props) {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/${panel.path}/register`);
    };

    return (
        <AuthLayout
            panel={panel}
            title="Create your account"
            description="Sign up to start using the panel."
            footer={
                panel.auth?.login_url ? (
                    <span>
                        Already have an account?{' '}
                        <Link href={panel.auth.login_url} className="text-foreground underline">
                            Sign in
                        </Link>
                    </span>
                ) : null
            }
        >
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        autoComplete="name"
                        required
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                    />
                    {form.errors.name ? (
                        <p className="mt-1 text-xs text-destructive">{form.errors.name}</p>
                    ) : null}
                </div>
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
                    <Label htmlFor="password">Password</Label>
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
                    {form.processing ? 'Creating…' : 'Create account'}
                </Button>
            </form>
        </AuthLayout>
    );
}
