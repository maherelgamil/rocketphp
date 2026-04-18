<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Support;

enum Color: string
{
    case Slate = 'slate';
    case Gray = 'gray';
    case Red = 'red';
    case Orange = 'orange';
    case Amber = 'amber';
    case Yellow = 'yellow';
    case Green = 'green';
    case Emerald = 'emerald';
    case Teal = 'teal';
    case Cyan = 'cyan';
    case Blue = 'blue';
    case Indigo = 'indigo';
    case Violet = 'violet';
    case Purple = 'purple';
    case Pink = 'pink';
    case Rose = 'rose';

    public function hex(): string
    {
        return match ($this) {
            self::Slate => '#64748b',
            self::Gray => '#6b7280',
            self::Red => '#dc2626',
            self::Orange => '#ea580c',
            self::Amber => '#f59e0b',
            self::Yellow => '#eab308',
            self::Green => '#16a34a',
            self::Emerald => '#10b981',
            self::Teal => '#14b8a6',
            self::Cyan => '#06b6d4',
            self::Blue => '#2563eb',
            self::Indigo => '#4f46e5',
            self::Violet => '#7c3aed',
            self::Purple => '#9333ea',
            self::Pink => '#db2777',
            self::Rose => '#e11d48',
        };
    }
}
