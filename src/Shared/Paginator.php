<?php

declare(strict_types=1);

namespace App\Shared;

/**
 * @template T
 */
final readonly class Paginator
{
    public int $page;
    public int $pageCount;

    /**
     * @param T[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        int $page,
        public int $perPage,
    ) {
        $this->pageCount = max(1, (int) ceil($total / $perPage));
        $this->page = min(max(1, $page), $this->pageCount);
    }

    public static function offset(int $page, int $perPage): int
    {
        return (max(1, $page) - 1) * $perPage;
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pageCount;
    }

    /**
     * Páginas visíveis na navegação, com `null` representando reticências.
     *
     * @return list<int|null>
     */
    public function pages(int $around = 2): array
    {
        $pages = [];
        $last = 0;
        for ($i = 1; $i <= $this->pageCount; $i++) {
            if ($i === 1 || $i === $this->pageCount || abs($i - $this->page) <= $around) {
                if ($last && $i - $last > 1) {
                    $pages[] = null;
                }
                $pages[] = $i;
                $last = $i;
            }
        }
        return $pages;
    }
}
