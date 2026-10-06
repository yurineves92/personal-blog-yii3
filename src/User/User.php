<?php

declare(strict_types=1);

namespace App\User;

use DateTimeImmutable;
use Yiisoft\Auth\IdentityInterface;

final readonly class User implements IdentityInterface
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public Role $role,
        public ?string $bio,
        public bool $isActive,
        public ?DateTimeImmutable $lastLoginAt,
        public DateTimeImmutable $createdAt,
        public int $postsCount = 0,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            role: Role::from((string) $row['role']),
            bio: $row['bio'] !== null ? (string) $row['bio'] : null,
            isActive: (bool) $row['is_active'],
            lastLoginAt: $row['last_login_at'] !== null ? new DateTimeImmutable((string) $row['last_login_at']) : null,
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            postsCount: (int) ($row['posts_count'] ?? 0),
        );
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = mb_substr($parts[0] ?? '?', 0, 1);
        $last = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    }
}
