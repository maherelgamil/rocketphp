import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

type DataPoint = { label: string; value: number };

type Props = {
    chartType: 'line' | 'bar' | 'area';
    data: DataPoint[];
    color: string;
};

const COMMON_PROPS = {
    margin: { top: 4, right: 4, left: -20, bottom: 0 },
};

const AXIS_PROPS = {
    tick: { fontSize: 11, fill: 'hsl(var(--muted-foreground))' },
    axisLine: false,
    tickLine: false,
};

const TOOLTIP_STYLE = {
    contentStyle: {
        background: 'hsl(var(--card))',
        border: '1px solid hsl(var(--border))',
        borderRadius: '6px',
        fontSize: 12,
    },
    itemStyle: { color: 'hsl(var(--foreground))' },
    cursor: { fill: 'hsl(var(--muted))' },
};

export default function ChartWidgetInner({ chartType, data, color }: Props) {
    const chartData = data.map((d) => ({ name: d.label, value: d.value }));

    if (chartType === 'bar') {
        return (
            <ResponsiveContainer width="100%" height={220}>
                <BarChart data={chartData} {...COMMON_PROPS}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="hsl(var(--border))" />
                    <XAxis dataKey="name" {...AXIS_PROPS} />
                    <YAxis {...AXIS_PROPS} />
                    <Tooltip {...TOOLTIP_STYLE} />
                    <Bar dataKey="value" fill={color} radius={[3, 3, 0, 0]} />
                </BarChart>
            </ResponsiveContainer>
        );
    }

    if (chartType === 'area') {
        return (
            <ResponsiveContainer width="100%" height={220}>
                <AreaChart data={chartData} {...COMMON_PROPS}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="hsl(var(--border))" />
                    <XAxis dataKey="name" {...AXIS_PROPS} />
                    <YAxis {...AXIS_PROPS} />
                    <Tooltip {...TOOLTIP_STYLE} />
                    <defs>
                        <linearGradient id="colorGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="5%" stopColor={color} stopOpacity={0.2} />
                            <stop offset="95%" stopColor={color} stopOpacity={0} />
                        </linearGradient>
                    </defs>
                    <Area type="monotone" dataKey="value" stroke={color} strokeWidth={2} fill="url(#colorGrad)" />
                </AreaChart>
            </ResponsiveContainer>
        );
    }

    return (
        <ResponsiveContainer width="100%" height={220}>
            <LineChart data={chartData} {...COMMON_PROPS}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="hsl(var(--border))" />
                <XAxis dataKey="name" {...AXIS_PROPS} />
                <YAxis {...AXIS_PROPS} />
                <Tooltip {...TOOLTIP_STYLE} />
                <Line type="monotone" dataKey="value" stroke={color} strokeWidth={2} dot={false} />
            </LineChart>
        </ResponsiveContainer>
    );
}
