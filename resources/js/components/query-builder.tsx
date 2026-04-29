import { Plus, Trash2 } from 'lucide-react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from './ui/select';

export type ConstraintSchema = {
    name: string;
    label: string;
    operators: Record<string, string>;
    input_types: Record<string, string>;
    options: Record<string, string> | null;
};

export type QbRule = {
    column?: string;
    operator?: string;
    value?: unknown;
};

export type QbGroup = {
    logic: 'and' | 'or';
    rules: Array<QbRule | QbGroup>;
};

export type QbState = QbGroup;

type Props = {
    state: QbState;
    constraints: ConstraintSchema[];
    onChange: (next: QbState) => void;
    __: (k: string) => string;
};

function isGroup(node: QbRule | QbGroup): node is QbGroup {
    return Array.isArray((node as QbGroup).rules);
}

export default function QueryBuilderTree({ state, constraints, onChange, __ }: Props) {
    return (
        <Group state={state} constraints={constraints} onChange={onChange} __={__} depth={0} />
    );
}

function Group({
    state,
    constraints,
    onChange,
    __,
    depth,
}: Props & { depth: number }) {
    const setLogic = (logic: 'and' | 'or') => onChange({ ...state, logic });

    const updateChild = (index: number, child: QbRule | QbGroup) => {
        const rules = [...state.rules];
        rules[index] = child;
        onChange({ ...state, rules });
    };

    const removeChild = (index: number) => {
        const rules = [...state.rules];
        rules.splice(index, 1);
        onChange({ ...state, rules });
    };

    const addRule = () => {
        onChange({ ...state, rules: [...state.rules, { column: '', operator: '', value: '' }] });
    };

    const addGroup = () => {
        onChange({
            ...state,
            rules: [...state.rules, { logic: 'and', rules: [] }],
        });
    };

    return (
        <div
            className={`space-y-2 rounded-md border border-dashed border-border p-3 ${
                depth > 0 ? 'bg-muted/30' : ''
            }`}
        >
            <div className="flex items-center gap-2">
                <span className="text-xs text-muted-foreground">{__('Match')}</span>
                <Select value={state.logic} onValueChange={(v) => setLogic(v as 'and' | 'or')}>
                    <SelectTrigger className="h-8 w-24">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="and">{__('AND')}</SelectItem>
                        <SelectItem value="or">{__('OR')}</SelectItem>
                    </SelectContent>
                </Select>
                <span className="text-xs text-muted-foreground">{__('of the following')}</span>
            </div>

            <div className="space-y-2">
                {state.rules.map((node, i) => (
                    <div key={i} className="flex items-start gap-2">
                        {isGroup(node) ? (
                            <div className="flex-1">
                                <Group
                                    state={node}
                                    constraints={constraints}
                                    onChange={(g) => updateChild(i, g)}
                                    __={__}
                                    depth={depth + 1}
                                />
                            </div>
                        ) : (
                            <RuleRow
                                rule={node}
                                constraints={constraints}
                                onChange={(r) => updateChild(i, r)}
                                __={__}
                            />
                        )}
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onClick={() => removeChild(i)}
                            aria-label={__('Remove')}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </div>
                ))}
            </div>

            <div className="flex gap-2">
                <Button type="button" size="sm" variant="outline" onClick={addRule}>
                    <Plus className="me-1 size-3" />
                    {__('Add rule')}
                </Button>
                <Button type="button" size="sm" variant="outline" onClick={addGroup}>
                    <Plus className="me-1 size-3" />
                    {__('Add group')}
                </Button>
            </div>
        </div>
    );
}

function RuleRow({
    rule,
    constraints,
    onChange,
    __,
}: {
    rule: QbRule;
    constraints: ConstraintSchema[];
    onChange: (r: QbRule) => void;
    __: (k: string) => string;
}) {
    const constraint = constraints.find((c) => c.name === rule.column);
    const operator = rule.operator ?? '';
    const inputType = constraint?.input_types[operator] ?? 'text';

    return (
        <div className="flex flex-1 flex-wrap items-center gap-2">
            <Select
                value={rule.column ?? ''}
                onValueChange={(v) => onChange({ ...rule, column: v, operator: '', value: '' })}
            >
                <SelectTrigger className="h-9 min-w-[140px]">
                    <SelectValue placeholder={__('Column')} />
                </SelectTrigger>
                <SelectContent>
                    {constraints.map((c) => (
                        <SelectItem key={c.name} value={c.name}>
                            {c.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            {constraint && (
                <Select
                    value={operator}
                    onValueChange={(v) => onChange({ ...rule, operator: v, value: '' })}
                >
                    <SelectTrigger className="h-9 min-w-[140px]">
                        <SelectValue placeholder={__('Operator')} />
                    </SelectTrigger>
                    <SelectContent>
                        {Object.entries(constraint.operators).map(([key, label]) => (
                            <SelectItem key={key} value={key}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}

            {operator && inputType !== 'none' && (
                <ValueInput
                    type={inputType}
                    value={rule.value}
                    options={constraint?.options ?? null}
                    onChange={(v) => onChange({ ...rule, value: v })}
                />
            )}
        </div>
    );
}

function ValueInput({
    type,
    value,
    options,
    onChange,
}: {
    type: string;
    value: unknown;
    options: Record<string, string> | null;
    onChange: (v: unknown) => void;
}) {
    if (type === 'number') {
        return (
            <Input
                type="number"
                className="h-9 w-32"
                value={value === undefined || value === null ? '' : String(value)}
                onChange={(e) => onChange(e.target.value)}
            />
        );
    }
    if (type === 'date') {
        return (
            <Input
                type="date"
                className="h-9 w-36"
                value={typeof value === 'string' ? value : ''}
                onChange={(e) => onChange(e.target.value)}
            />
        );
    }
    if (type === 'date_range' || type === 'number_range') {
        const v = (value as { from?: unknown; to?: unknown }) ?? {};
        const html = type === 'date_range' ? 'date' : 'number';
        return (
            <div className="flex items-center gap-1">
                <Input
                    type={html}
                    className="h-9 w-32"
                    value={v.from === undefined || v.from === null ? '' : String(v.from)}
                    onChange={(e) => onChange({ ...v, from: e.target.value })}
                />
                <span className="text-xs">→</span>
                <Input
                    type={html}
                    className="h-9 w-32"
                    value={v.to === undefined || v.to === null ? '' : String(v.to)}
                    onChange={(e) => onChange({ ...v, to: e.target.value })}
                />
            </div>
        );
    }
    if (type === 'multi_select' && options) {
        const arr = Array.isArray(value) ? (value as string[]) : [];
        return (
            <div className="flex flex-wrap gap-1">
                {Object.entries(options).map(([k, label]) => {
                    const on = arr.includes(k);
                    return (
                        <button
                            key={k}
                            type="button"
                            onClick={() => {
                                const next = on ? arr.filter((x) => x !== k) : [...arr, k];
                                onChange(next);
                            }}
                            className={`rounded border px-2 py-1 text-xs ${
                                on
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-border'
                            }`}
                        >
                            {label}
                        </button>
                    );
                })}
            </div>
        );
    }
    return (
        <Input
            type="text"
            className="h-9 flex-1 min-w-[140px]"
            value={typeof value === 'string' ? value : ''}
            onChange={(e) => onChange(e.target.value)}
        />
    );
}
