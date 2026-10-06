<?php

declare(strict_types=1);

namespace App\Api;

use DateTimeImmutable;

final readonly class ApiToken
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public ?DateTimeImmutable $lastUsedAt,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            name: (string) $row['name'],
            lastUsedAt: $row['last_used_at'] !== null ? new DateTimeImmutable((string) $row['last_used_at']) : null,
            expiresAt: new DateTimeImmutable((string) $row['expires_at']),
            createdAt: new DateTimeImmutable((string) $row['created_at']),
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }
}
