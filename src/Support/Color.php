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

    /** HSL values (no wrapper) suitable for CSS custom properties, e.g. "221 83% 53%". */
    public function hsl(): string
    {
        return match ($this) {
            self::Slate => '215 16% 47%',
            self::Gray => '220 9% 46%',
            self::Red => '0 72% 51%',
            self::Orange => '25 95% 48%',
            self::Amber => '38 92% 50%',
            self::Yellow => '48 96% 47%',
            self::Green => '142 76% 36%',
            self::Emerald => '160 84% 39%',
            self::Teal => '173 80% 40%',
            self::Cyan => '189 96% 43%',
            self::Blue => '221 83% 53%',
            self::Indigo => '239 74% 57%',
            self::Violet => '262 83% 58%',
            self::Purple => '272 81% 56%',
            self::Pink => '330 71% 51%',
            self::Rose => '347 77% 50%',
        };
    }
}
