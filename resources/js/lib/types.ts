export type StatWidget = {
    type: 'stat';
    label: string;
    value: string | number;
    column_span: number | string;
};

export type TableWidget = {
    type: 'table';
    title: string;
    column_span: number | string;
    columns: { name: string; label: string }[];
    rows: Record<string, unknown>[];
};

export type ChartWidgetType = {
    type: 'chart';
    chart_type: 'line' | 'bar' | 'area';
    title: string;
    color: string;
    column_span: number | string;
    data: { label: string; value: number }[];
};

export type RecentRecordsWidget = {
    type: 'recent_records';
    title: string;
    column_span: number | string;
    columns: { name: string; label: string }[];
    rows: Record<string, unknown>[];
    resource_url: string | null;
};

export type ActivityFeedWidgetType = {
    type: 'activity_feed';
    title: string;
    column_span: number | string;
    items: { title: string; time: string | null; icon: string }[];
};

export type DashboardWidget =
    | StatWidget
    | TableWidget
    | ChartWidgetType
    | RecentRecordsWidget
    | ActivityFeedWidgetType;

export type HtmlBlock = { type: 'html'; html: string };
export type WidgetBlock = { type: 'widget'; widget: DashboardWidget };
export type GridBlock = { type: 'grid'; columns: number; blocks: Block[] };

export type Block = HtmlBlock | WidgetBlock | GridBlock;

export type PageAction = {
    name: string;
    label: string;
    url: string | null;
    has_action: boolean;
    requires_confirmation: boolean;
    confirmation_message: string | null;
};