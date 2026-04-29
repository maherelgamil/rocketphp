import type { ReactNode } from 'react';

export type AuthPanelProp = {
    id: string;
    brand: string;
    path: string;
    theme?: Record<string, string>;
    auth?: {
        login_url?: string | null;
        register_url?: string | null;
        forgot_password_url?: string | null;
    };
};

type Props = {
    panel: AuthPanelProp;
    title: string;
    description?: string;
    children: ReactNode;
    footer?: ReactNode;
};

export default function AuthLayout({ panel, title, description, children, footer }: Props) {
    const themeStyle: Record<string, string> = {};
    for (const [k, v] of Object.entries(panel.theme ?? {})) {
        themeStyle[`--rocket-${k}`] = v;
    }

    return (
        <div
            className="flex min-h-screen items-center justify-center bg-muted/30 px-4 py-10"
            style={themeStyle as React.CSSProperties}
        >
            <div className="w-full max-w-md">
                <div className="mb-8 text-center">
                    <h1 className="text-2xl font-semibold tracking-tight">{panel.brand}</h1>
                </div>
                <div className="rounded-lg border bg-card p-6 shadow-sm">
                    <div className="mb-6">
                        <h2 className="text-xl font-semibold">{title}</h2>
                        {description ? (
                            <p className="mt-1 text-sm text-muted-foreground">{description}</p>
                        ) : null}
                    </div>
                    {children}
                </div>
                {footer ? <div className="mt-6 text-center text-sm text-muted-foreground">{footer}</div> : null}
            </div>
        </div>
    );
}
