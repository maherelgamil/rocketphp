export type Translator = (key: string, replacements?: Record<string, string | number>) => string;

export function create__(translations: Record<string, string>): Translator {
    return (key, replacements) => {
        let str = translations[key] ?? key;

        if (replacements) {
            for (const [k, v] of Object.entries(replacements)) {
                str = str.replaceAll(`:${k}`, String(v));
            }
        }

        return str;
    };
}
