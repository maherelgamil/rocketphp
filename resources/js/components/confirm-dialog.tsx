import type { ReactNode } from 'react';
import type { Translator } from '../lib/i18n';
import { Button } from './ui/button';

type Props = {
    open: boolean;
    title: string;
    description: string;
    confirmLabel: string;
    destructive?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
    children?: ReactNode;
    __?: Translator;
};

export default function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel,
    destructive = false,
    onConfirm,
    onCancel,
    __ = (key) => key,
}: Props) {
    if (!open) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            role="presentation"
            onClick={onCancel}
            onKeyDown={(e) => e.key === 'Escape' && onCancel()}
        >
            <div
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="confirm-title"
                aria-describedby="confirm-desc"
                className="w-full max-w-md rounded-lg border bg-card p-6 shadow-lg"
                onClick={(e) => e.stopPropagation()}
            >
                <h2 id="confirm-title" className="text-lg font-semibold">
                    {title}
                </h2>
                <p id="confirm-desc" className="mt-2 text-sm text-muted-foreground">
                    {description}
                </p>
                <div className="mt-6 flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onCancel}>
                        {__('Cancel')}
                    </Button>
                    <Button
                        type="button"
                        variant={destructive ? 'destructive' : 'default'}
                        onClick={onConfirm}
                    >
                        {confirmLabel}
                    </Button>
                </div>
            </div>
        </div>
    );
}
