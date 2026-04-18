import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

type FlashProps = {
    flash?: {
        success?: string | null;
        error?: string | null;
    } | null;
};

export function useFlashToast() {
    const { flash } = usePage<FlashProps>().props;
    const lastSuccess = useRef<string | null>(null);
    const lastError = useRef<string | null>(null);

    useEffect(() => {
        const success = flash?.success ?? null;
        if (success && success !== lastSuccess.current) {
            lastSuccess.current = success;
            toast.success(success);
        } else if (!success) {
            lastSuccess.current = null;
        }
    }, [flash?.success]);

    useEffect(() => {
        const error = flash?.error ?? null;
        if (error && error !== lastError.current) {
            lastError.current = error;
            toast.error(error);
        } else if (!error) {
            lastError.current = null;
        }
    }, [flash?.error]);
}
