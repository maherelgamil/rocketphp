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
