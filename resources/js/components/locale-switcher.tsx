import { router } from '@inertiajs/react';
import { Check, Languages } from 'lucide-react';
import React from 'react';
import { Button } from './ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from './ui/dropdown-menu';

type LocaleSwitcherProps = {
    locale: string;
    availableLocales: string[];
    switchUrl: string;
};

const LOCALE_NAMES: Record<string, string> = {
    en: 'English',
    ar: 'العربية',
    fr: 'Français',
    es: 'Español',
    de: 'Deutsch',
    it: 'Italiano',
    pt: 'Português',
    nl: 'Nederlands',
    ru: 'Русский',
    zh: '中文',
    ja: '日本語',
    ko: '한국어',
    tr: 'Türkçe',
    he: 'עברית',
    fa: 'فارسی',
    ur: 'اردو',
    hi: 'हिन्दी',
    pl: 'Polski',
    sv: 'Svenska',
    cs: 'Čeština',
    el: 'Ελληνικά',
    th: 'ไทย',
    vi: 'Tiếng Việt',
    id: 'Bahasa Indonesia',
};

function localeName(code: string): string {
    return LOCALE_NAMES[code] ?? code.toUpperCase();
}

export default function LocaleSwitcher({ locale, availableLocales, switchUrl }: LocaleSwitcherProps) {
    const handleLocaleChange = (newLocale: string) => {
        if (newLocale === locale) return;
        router.post(switchUrl, { locale: newLocale });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="h-9 gap-1.5 px-2 font-normal">
                    <Languages className="size-4 text-muted-foreground" />
                    <span className="hidden text-sm sm:inline">{localeName(locale)}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-[10rem]">
                {availableLocales.map((loc) => (
                    <DropdownMenuItem
                        key={loc}
                        onSelect={() => handleLocaleChange(loc)}
                        className="flex items-center justify-between gap-2"
                    >
                        <span>{localeName(loc)}</span>
                        {loc === locale && <Check className="size-4 text-muted-foreground" />}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
