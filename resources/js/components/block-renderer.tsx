import { renderWidget } from './widget-renderer';
import type { Block } from '../lib/types';

export function renderBlock(
    block: Block,
    key: number,
    panelPath: string,
    slug: string
): React.ReactNode {
    if (block.type === 'html') {
        return (
            <div key={key} dangerouslySetInnerHTML={{ __html: block.html }} />
        );
    }

    if (block.type === 'widget') {
        return <div key={key}>{renderWidget(block.widget, 0)}</div>;
    }

    if (block.type === 'grid') {
        return (
            <div
                key={key}
                style={{ gridTemplateColumns: `repeat(${block.columns}, minmax(0, 1fr))` }}
                className="grid gap-6"
            >
                {block.blocks.map((child, i) => renderBlock(child, i, panelPath, slug))}
            </div>
        );
    }

    return null;
}

export function renderContent(
    content: Block[],
    panelPath: string,
    slug: string
): React.ReactNode {
    return content.map((block, i) => renderBlock(block, i, panelPath, slug));
}