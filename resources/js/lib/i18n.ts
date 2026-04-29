export type Translator = (key: string, replacements?: Record<string, string | number>) => string;

export function create__(translations: Record<string, string>): Translator {
    return (key, replacements) => {
        let str = translations[key] ?? key;

        if (replacements) {
            const entries = Object.entries(replacements).sort(
                ([a], [b]) => b.length - a.length,
            );
            for (const [k, v] of entries) {
                str = str.replaceAll(`:${k}`, String(v));
            }
        }

        return str;
    };
}
