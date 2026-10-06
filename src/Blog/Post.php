<?php

declare(strict_types=1);

namespace App\Blog;

use DateTimeImmutable;

final readonly class Post
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $content,
        public ?string $coverUrl,
        public ?int $categoryId,
        public ?string $categoryName,
        public ?string $categorySlug,
        public int $authorId,
        public string $authorName,
        public ?int $reviewerId,
        public ?string $reviewerName,
        public PostStatus $status,
        public ?string $reviewNote,
        public int $views,
        public ?DateTimeImmutable $publishedAt,
        public ?DateTimeImmutable $submittedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function fromRow(array $row): self
    {
        $date = static fn(mixed $value): ?DateTimeImmutable => $value !== null ? new DateTimeImmutable((string) $value) : null;

        return new self(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            excerpt: $row['excerpt'] !== null && $row['excerpt'] !== '' ? (string) $row['excerpt'] : null,
            content: (string) $row['content'],
            coverUrl: $row['cover_url'] !== null && $row['cover_url'] !== '' ? (string) $row['cover_url'] : null,
            categoryId: $row['category_id'] !== null ? (int) $row['category_id'] : null,
            categoryName: $row['category_name'] ?? null,
            categorySlug: $row['category_slug'] ?? null,
            authorId: (int) $row['author_id'],
            authorName: (string) ($row['author_name'] ?? ''),
            reviewerId: $row['reviewer_id'] !== null ? (int) $row['reviewer_id'] : null,
            reviewerName: $row['reviewer_name'] ?? null,
            status: PostStatus::from((string) $row['status']),
            reviewNote: $row['review_note'] !== null && $row['review_note'] !== '' ? (string) $row['review_note'] : null,
            views: (int) $row['views'],
            publishedAt: $date($row['published_at']),
            submittedAt: $date($row['submitted_at']),
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            updatedAt: new DateTimeImmutable((string) $row['updated_at']),
        );
    }

    public function summary(int $length = 180): string
    {
        if ($this->excerpt !== null) {
            return $this->excerpt;
        }

        $plain = trim((string) preg_replace(
            ['/```.*?```/s', '/!\[[^\]]*\]\([^)]*\)/', '/\[([^\]]*)\]\([^)]*\)/', '/[#>*_`~\-]+/', '/\s+/'],
            ['', '', '$1', '', ' '],
            $this->content,
        ));

        return mb_strlen($plain) > $length ? rtrim(mb_substr($plain, 0, $length)) . '…' : $plain;
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published;
    }
}
