<?php

declare(strict_types=1);

namespace App\Blog;

final readonly class Category
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $postsCount = 0,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            description: $row['description'] !== null && $row['description'] !== '' ? (string) $row['description'] : null,
            postsCount: (int) ($row['posts_count'] ?? 0),
        );
    }
}
