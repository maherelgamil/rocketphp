import { Head, useForm, usePage } from '@inertiajs/react';
import PanelShell from '../components/panel-shell';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';

type ProfileUser = {
    name: string;
    email: string;
};

type Props = {
    panel: Parameters<typeof PanelShell>[0]['panel'];
    user: ProfileUser;
};

export default function Profile({ panel, user }: Props) {
    const flash = (usePage().props as { flash?: { status?: string } }).flash ?? {};
    const form = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/${panel.path}/profile`, {
            onSuccess: () => form.reset('current_password', 'password', 'password_confirmation'),
        });
    };

    return (
        <PanelShell panel={panel}>
            <Head title="Profile" />
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight">Profile</h1>
                <p className="mt-1 text-sm text-muted-foreground">Update your account details.</p>
            </div>

            {flash.status === 'profile-updated' ? (
                <p className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                    Profile updated.
                </p>
            ) : null}

            <form onSubmit={submit} className="max-w-xl space-y-6">
                <div className="space-y-4 rounded-lg border bg-card p-6">
                    <h2 className="text-sm font-medium">Account</h2>
                    <div>
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
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
                            required
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                        />
                        {form.errors.email ? (
                            <p className="mt-1 text-xs text-destructive">{form.errors.email}</p>
                        ) : null}
                    </div>
                </div>

                <div className="space-y-4 rounded-lg border bg-card p-6">
                    <h2 className="text-sm font-medium">Change password</h2>
                    <div>
                        <Label htmlFor="current_password">Current password</Label>
                        <Input
                            id="current_password"
                            type="password"
                            autoComplete="current-password"
                            value={form.data.current_password}
                            onChange={(e) => form.setData('current_password', e.target.value)}
                        />
                        {form.errors.current_password ? (
                            <p className="mt-1 text-xs text-destructive">{form.errors.current_password}</p>
                        ) : null}
                    </div>
                    <div>
                        <Label htmlFor="password">New password</Label>
                        <Input
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                        />
                        {form.errors.password ? (
                            <p className="mt-1 text-xs text-destructive">{form.errors.password}</p>
                        ) : null}
                    </div>
                    <div>
                        <Label htmlFor="password_confirmation">Confirm new password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password_confirmation}
                            onChange={(e) => form.setData('password_confirmation', e.target.value)}
                        />
                    </div>
                </div>

                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Saving…' : 'Save changes'}
                </Button>
            </form>
        </PanelShell>
    );
}
