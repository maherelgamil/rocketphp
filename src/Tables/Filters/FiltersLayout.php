<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Tables\Filters;

enum FiltersLayout: string
{
    case Dropdown = 'dropdown';
    case AboveContent = 'above_content';
    case AboveContentCollapsible = 'above_content_collapsible';
    case BelowContent = 'below_content';
    case LeftSidebar = 'left_sidebar';
    case RightSidebar = 'right_sidebar';
}
