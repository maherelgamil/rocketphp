<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Dashboard\Concerns;

trait CanRenderOnPages
{
    /** @var array<int, string> */
    private array $pages = [];

    /**
     * @param  array<int, string>  $pages
     */
    public function only(array $pages): self
    {
        $this->pages = $pages;

        return $this;
    }

    public function shouldRenderOnPage(string $page): bool
    {
        return $this->pages === [] || in_array($page, $this->pages, true);
    }
}