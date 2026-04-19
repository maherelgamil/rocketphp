import { SearchableSelect } from './searchable-select';
import { Input } from './ui/input';
import { Label } from './ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from './ui/select';
import { Switch } from './ui/switch';
import { Textarea } from './ui/textarea';

export type FieldSchema = {
    type: string;
    name: string;
    label: string;
    placeholder: string | null;
    helper_text: string | null;
    default: unknown;
    disabled: boolean;
    required: boolean;
    extra: Record<string, unknown>;
};

type Props = {
    field: FieldSchema;
    value: unknown;
    error?: string;
    onChange: (value: unknown) => void;
};

export default function FormField({ field, value, error, onChange }: Props) {
    return (
        <div className="space-y-2">
            <Label htmlFor={field.name}>
                {field.label}
                {field.required && <span className="text-destructive">*</span>}
            </Label>

            {renderControl(field, value, onChange)}

            {field.helper_text && !error && (
                <p className="text-xs text-muted-foreground">{field.helper_text}</p>
            )}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}

function renderControl(
    field: FieldSchema,
    value: unknown,
    onChange: (value: unknown) => void,
) {
    switch (field.type) {
        case 'textarea': {
            const rows = (field.extra.rows as number | undefined) ?? 4;
            return (
                <Textarea
                    id={field.name}
                    name={field.name}
                    rows={rows}
                    placeholder={field.placeholder ?? undefined}
                    disabled={field.disabled}
                    value={stringValue(value)}
                    onChange={(e) => onChange(e.target.value)}
                />
            );
        }

        case 'select': {
            const lookupUrl = field.extra.lookup_url as string | null | undefined;
            if (field.extra.searchable && lookupUrl) {
                return (
                    <SearchableSelect
                        id={field.name}
                        value={stringValue(value)}
                        onChange={(v) => onChange(v)}
                        placeholder={field.placeholder}
                        disabled={field.disabled}
                        nullable={!field.required}
                        lookupUrl={lookupUrl}
                    />
                );
            }
            const options = (field.extra.options ?? {}) as Record<string, string>;
            return (
                <Select
                    value={stringValue(value)}
                    onValueChange={(v) => onChange(v)}
                    disabled={field.disabled}
                >
                    <SelectTrigger id={field.name}>
                        <SelectValue placeholder={field.placeholder ?? 'Select...'} />
                    </SelectTrigger>
                    <SelectContent>
                        {Object.entries(options).map(([key, label]) => (
                            <SelectItem key={key} value={key}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            );
        }

        case 'toggle':
            return (
                <div className="flex items-center gap-3">
                    <Switch
                        id={field.name}
                        checked={Boolean(value)}
                        onCheckedChange={(v) => onChange(v)}
                        disabled={field.disabled}
                    />
                </div>
            );

        case 'checkbox':
            return (
                <label className="flex items-center gap-2">
                    <input
                        id={field.name}
                        name={field.name}
                        type="checkbox"
                        checked={Boolean(value)}
                        onChange={(e) => onChange(e.target.checked)}
                        disabled={field.disabled}
                        className="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                    />
                </label>
            );

        case 'radio': {
            const options = (field.extra.options ?? {}) as Record<string, string>;
            const inline = Boolean(field.extra.inline);
            return (
                <div className={inline ? 'flex flex-wrap gap-4' : 'flex flex-col gap-2'}>
                    {Object.entries(options).map(([key, label]) => (
                        <label key={key} className="flex items-center gap-2 text-sm">
                            <input
                                type="radio"
                                name={field.name}
                                value={key}
                                checked={stringValue(value) === key}
                                onChange={() => onChange(key)}
                                disabled={field.disabled}
                                className="h-4 w-4 border-input text-primary focus:ring-primary"
                            />
                            <span>{label}</span>
                        </label>
                    ))}
                </div>
            );
        }

        case 'multi_select': {
            const options = (field.extra.options ?? {}) as Record<string, string>;
            const selected = Array.isArray(value) ? (value as string[]).map(String) : [];
            const toggle = (key: string) => {
                const next = selected.includes(key)
                    ? selected.filter((k) => k !== key)
                    : [...selected, key];
                onChange(next);
            };
            return (
                <div className="flex flex-col gap-2 rounded-md border border-input p-3">
                    {Object.entries(options).map(([key, label]) => (
                        <label key={key} className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={selected.includes(key)}
                                onChange={() => toggle(key)}
                                disabled={field.disabled}
                                className="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                            />
                            <span>{label}</span>
                        </label>
                    ))}
                </div>
            );
        }

        case 'date': {
            const withTime = Boolean(field.extra.with_time);
            return (
                <Input
                    id={field.name}
                    name={field.name}
                    type={withTime ? 'datetime-local' : 'date'}
                    placeholder={field.placeholder ?? undefined}
                    disabled={field.disabled}
                    value={stringValue(value)}
                    onChange={(e) => onChange(e.target.value)}
                />
            );
        }

        case 'file': {
            const isImage = Boolean(field.extra.image);
            const accepted = (field.extra.accepted_mimes as string[] | undefined) ?? [];
            const currentPath = (field.extra.current as string | null | undefined) ?? null;
            return (
                <div className="space-y-2">
                    <Input
                        id={field.name}
                        name={field.name}
                        type="file"
                        accept={
                            accepted.length > 0
                                ? accepted.map((m) => (m.includes('/') ? m : `.${m}`)).join(',')
                                : isImage
                                  ? 'image/*'
                                  : undefined
                        }
                        disabled={field.disabled}
                        onChange={(e) => onChange(e.target.files?.[0] ?? null)}
                    />
                    {value instanceof File && (
                        <p className="text-xs text-muted-foreground">
                            Selected: {value.name}
                        </p>
                    )}
                    {!(value instanceof File) && currentPath && (
                        <p className="text-xs text-muted-foreground">
                            Current: <span className="font-mono">{currentPath}</span>
                        </p>
                    )}
                </div>
            );
        }

        case 'text':
        default: {
            const inputType = (field.extra.input_type as string | undefined) ?? 'text';
            return (
                <Input
                    id={field.name}
                    name={field.name}
                    type={inputType}
                    placeholder={field.placeholder ?? undefined}
                    disabled={field.disabled}
                    value={stringValue(value)}
                    onChange={(e) =>
                        onChange(
                            inputType === 'number'
                                ? e.target.value === ''
                                    ? ''
                                    : Number(e.target.value)
                                : e.target.value,
                        )
                    }
                />
            );
        }
    }
}

function stringValue(value: unknown): string {
    if (value === null || value === undefined) return '';
    return String(value);
}
